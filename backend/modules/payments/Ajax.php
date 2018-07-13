<?php
namespace Bookly\Backend\Modules\Payments;

use Bookly\Lib;

/**
 * Class Ajax
 * @package Bookly\Backend\Modules\Payments
 */
class Ajax extends Lib\Base\Ajax
{
    /**
     * @inheritdoc
     */
    protected static function permissions()
    {
        return array(
            'getPaymentDetails'    => 'user',
            'completePayment'      => 'user',
            'addPaymentAdjustment' => 'user',
            'getPaymentInfo'       => 'user',
        );
    }

    /**
     * Get payments.
     */
    public static function getPayments()
    {
        $columns = self::parameter( 'columns' );
        $order   = self::parameter( 'order' );
        $filter  = self::parameter( 'filter' );

        $query = Lib\Entities\Payment::query( 'p' )
            ->select( 'p.id, p.created, p.type, p.paid, p.total, p.status, p.details, c.full_name customer, st.full_name provider, s.title service, a.start_date' )
            ->leftJoin( 'CustomerAppointment', 'ca', 'ca.payment_id = p.id' )
            ->leftJoin( 'Customer', 'c', 'c.id = ca.customer_id' )
            ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
            ->leftJoin( 'Service', 's', 's.id = COALESCE(ca.compound_service_id, a.service_id)' )
            ->leftJoin( 'Staff', 'st', 'st.id = a.staff_id' )
            ->groupBy( 'p.id' );

        // Filters.
        list ( $start, $end ) = explode( ' - ', $filter['created'], 2 );
        $end = date( 'Y-m-d', strtotime( '+1 day', strtotime( $end ) ) );

        $query->whereBetween( 'p.created', $start, $end );

        if ( $filter['id'] != '' ) {
            $query->where( 'p.id', $filter['id'] );
        }

        if ( $filter['type'] != '' ) {
            $query->where( 'p.type', $filter['type'] );
        }

        if ( $filter['staff'] != '' ) {
            $query->where( 'st.id', $filter['staff'] );
        }

        if ( $filter['service'] != '' ) {
            $query->where( 's.id', $filter['service'] );
        }

        if ( $filter['status'] != '' ) {
            $query->where( 'p.status', $filter['status'] );
        }

        if ( $filter['customer'] != '' ) {
            $query->where( 'ca.customer_id', $filter['customer'] );
        }

        foreach ( $order as $sort_by ) {
            $query->sortBy( $columns[ $sort_by['column'] ]['data'] )
                ->order( $sort_by['dir'] == 'desc' ? Lib\Query::ORDER_DESCENDING : Lib\Query::ORDER_ASCENDING );
        }

        $payments = $query->fetchArray();

        $data  = array();
        $total = 0;
        foreach ( $payments as $payment ) {
            $details  = json_decode( $payment['details'], true );
            $multiple = count( $details['items'] ) > 1
                ? ' <span class="glyphicon glyphicon-shopping-cart" title="' . esc_attr( __( 'See details for more items', 'bookly' ) ) . '"></span>'
                : '';

            $paid_title = Lib\Utils\Price::format( $payment['paid'] );
            if ( $payment['paid'] != $payment['total'] ) {
                $paid_title = sprintf( __( '%s of %s', 'bookly' ), $paid_title, Lib\Utils\Price::format( $payment['total'] ) );
            }

            $data[] = array(
                'id'         => $payment['id'],
                'created'    => Lib\Utils\DateTime::formatDateTime( $payment['created'] ),
                'type'       => Lib\Entities\Payment::typeToString( $payment['type'] ),
                'customer'   => $payment['customer'] ?: $details['customer'],
                'provider'   => ( $payment['provider'] ?: $details['items'][0]['staff_name'] ) . $multiple,
                'service'    => ( $payment['service'] ?: $details['items'][0]['service_name'] ) . $multiple,
                'start_date' => ( $payment['start_date']
                    ? Lib\Utils\DateTime::formatDateTime( $payment['start_date'] )
                    : Lib\Utils\DateTime::formatDateTime( $details['items'][0]['appointment_date'] ) ) . $multiple,
                'paid'       => $paid_title,
                'status'     => Lib\Entities\Payment::statusToString( $payment['status'] ),
            );

            $total += $payment['paid'];
        }

        wp_send_json( array(
            'draw'            => ( int ) self::parameter( 'draw' ),
            'recordsTotal'    => count( $data ),
            'recordsFiltered' => count( $data ),
            'data'            => $data,
            'total'           => Lib\Utils\Price::format( $total ),
        ) );
    }

    /**
     * Get payment details.
     */
    public static function getPaymentDetails()
    {
        $payment = Lib\Entities\Payment::find( self::parameter( 'payment_id' ) );
        if ( $payment ) {
            $data = $payment->getPaymentData();
            $show_deposit = Lib\Config::depositPaymentsEnabled();
            if ( ! $show_deposit ) {
                foreach ( $data['payment']['items'] as $item ) {
                    if ( isset( $item['deposit_format'] ) ) {
                        $show_deposit = true;
                        break;
                    }
                }
            }

            $data['show'] = array(
                'coupons' => Lib\Config::couponsEnabled(),
                'customer_groups' => Lib\Config::customerGroupsEnabled(),
                'deposit' => (int) $show_deposit,
                'gateway' => Proxy\Shared::paymentSpecificPriceExists( $data['payment']['type'] ) === true,
                'taxes'   => (int) ( Lib\Config::taxesEnabled() || $data['payment']['tax_total'] > 0 ),
            );
            wp_send_json_success( array( 'html' => self::renderTemplate( 'details', $data, false ) ) );
        }

        wp_send_json_error( array( 'html' => __( 'Payment is not found.', 'bookly' ) ) );
    }

    /**
     * Adjust payment.
     */
    public static function addPaymentAdjustment()
    {
        $payment_id = self::parameter( 'payment_id' );
        $reason     = self::parameter( 'reason' );
        $tax        = self::parameter( 'tax', 0 );
        $amount     = self::parameter( 'amount' );

        $payment = new Lib\Entities\Payment();
        $payment->load( $payment_id );

        if ( $payment && is_numeric( $amount ) ) {
            $details = json_decode( $payment->getDetails(), true );

            $details['adjustments'][] = compact( 'reason', 'amount', 'tax' );
            $payment
                ->setDetails( json_encode( $details ) )
                ->setTotal( $payment->getTotal() + $amount )
                ->setTax( $payment->getTax() + $tax )
                ->save();
        }

        wp_send_json_success();
    }

    /**
     * Delete payments.
     */
    public static function deletePayments()
    {
        $payment_ids = array_map( 'intval', self::parameter( 'data', array() ) );
        Lib\Entities\Payment::query()->delete()->whereIn( 'id', $payment_ids )->execute();
        wp_send_json_success();
    }

    /**
     * Complete payment.
     */
    public static function completePayment()
    {
        $payment = Lib\Entities\Payment::find( self::parameter( 'payment_id' ) );
        $details = json_decode( $payment->getDetails(), true );
        $details['tax_paid'] = $payment->getTax();
        $payment
            ->setPaid( $payment->getTotal() )
            ->setStatus( Lib\Entities\Payment::STATUS_COMPLETED )
            ->setDetails( json_encode( $details ) )
            ->save();

        $payment_title = Lib\Utils\Price::format( $payment->getPaid() );
        if ( $payment->getPaid() != $payment->getTotal() ) {
            $payment_title = sprintf( __( '%s of %s', 'bookly' ), $payment_title, Lib\Utils\Price::format( $payment->getTotal() ) );
        }
        $payment_title .= sprintf(
            ' %s <span%s>%s</span>',
            Lib\Entities\Payment::typeToString( $payment->getType() ),
            $payment->getStatus() == Lib\Entities\Payment::STATUS_PENDING ? ' class="text-danger"' : '',
            Lib\Entities\Payment::statusToString( $payment->getStatus() )
        );

        wp_send_json_success( array( 'payment_title' => $payment_title ) );
    }

    /**
     * Get payment info
     */
    public static function getPaymentInfo()
    {
        $payment = Lib\Entities\Payment::find( self::parameter( 'payment_id' ) );

        if ( $payment ) {
            $payment_title = Lib\Utils\Price::format( $payment->getPaid() );
            if ( $payment->getPaid() != $payment->getTotal() ) {
                $payment_title = sprintf( __( '%s of %s', 'bookly' ), $payment_title, Lib\Utils\Price::format( $payment->getTotal() ) );
            }
            $payment_title .= sprintf(
                ' %s <span%s>%s</span>',
                Lib\Entities\Payment::typeToString( $payment->getType() ),
                $payment->getStatus() == Lib\Entities\Payment::STATUS_PENDING ? ' class="text-danger"' : '',
                Lib\Entities\Payment::statusToString( $payment->getStatus() )
            );

            wp_send_json_success( array( 'payment_title' => $payment_title, 'payment_type' => $payment->getPaid() == $payment->getTotal() ? 'full' : 'partial' ) );
        }
    }
}