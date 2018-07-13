<?php
namespace Bookly\Backend\Modules\Customers;

use Bookly\Lib;

/**
 * Class Ajax
 * @package Bookly\Backend\Modules\Customers
 */
class Ajax extends Lib\Base\Ajax
{
    /**
     * @inheritdoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'user' );
    }

    /**
     * Get list of customers.
     */
    public static function getCustomers()
    {
        global $wpdb;

        $columns = self::parameter( 'columns' );
        $order   = self::parameter( 'order' );
        $filter  = self::parameter( 'filter' );

        $query = Lib\Entities\Customer::query( 'c' );

        $total = $query->count();

        $select = 'SQL_CALC_FOUND_ROWS c.*,
                (
                    SELECT MAX(a.start_date) FROM ' . Lib\Entities\Appointment::getTableName() . ' a
                        LEFT JOIN ' . Lib\Entities\CustomerAppointment::getTableName() . ' ca ON ca.appointment_id = a.id
                            WHERE ca.customer_id = c.id
                ) AS last_appointment,
                (
                    SELECT COUNT(DISTINCT ca.appointment_id) FROM ' . Lib\Entities\CustomerAppointment::getTableName() . ' ca
                        WHERE ca.customer_id = c.id
                ) AS total_appointments,
                (
                    SELECT SUM(p.total) FROM ' . Lib\Entities\Payment::getTableName() . ' p
                        WHERE p.id IN (
                            SELECT DISTINCT ca.payment_id FROM ' . Lib\Entities\CustomerAppointment::getTableName() . ' ca
                                WHERE ca.customer_id = c.id
                        )
                ) AS payments,
                wpu.display_name AS wp_user';

        $select = Proxy\CustomerGroups::prepareCustomerSelect( $select );

        $query
            ->select( $select )
            ->tableJoin( $wpdb->users, 'wpu', 'wpu.ID = c.wp_user_id' )
            ->groupBy( 'c.id' );

        $query = Proxy\CustomerGroups::prepareCustomerQuery( $query );

        if ( $filter != '' ) {
            $search_value = Lib\Query::escape( $filter );
            $query
                ->whereLike( 'c.full_name', "%{$search_value}%" )
                ->whereLike( 'c.phone', "%{$search_value}%", 'OR' )
                ->whereLike( 'c.email', "%{$search_value}%", 'OR' )
                ->whereLike( 'c.info_fields', "%{$search_value}%", 'OR' )
            ;
        }

        foreach ( $order as $sort_by ) {
            $query
                ->sortBy( str_replace( '.', '_', $columns[ $sort_by['column'] ]['data'] ) )
                ->order( $sort_by['dir'] == 'desc' ? Lib\Query::ORDER_DESCENDING : Lib\Query::ORDER_ASCENDING );
        }

        $query->limit( self::parameter( 'length' ) )->offset( self::parameter( 'start' ) );

        $data = array();
        foreach ( $query->fetchArray() as $row ) {

            $address = Lib\Utils\Common::getFullAddressByCustomerData( $row );

            $customer_data = array(
                'id'                 => $row['id'],
                'wp_user_id'         => $row['wp_user_id'],
                'wp_user'            => $row['wp_user'],
                'facebook_id'        => $row['facebook_id'],
                'group_id'           => $row['group_id'],
                'full_name'          => $row['full_name'],
                'first_name'         => $row['first_name'],
                'last_name'          => $row['last_name'],
                'phone'              => $row['phone'],
                'email'              => $row['email'],
                'country'            => $row['country'],
                'state'              => $row['state'],
                'postcode'           => $row['postcode'],
                'city'               => $row['city'],
                'street'             => $row['street'],
                'additional_address' => $row['additional_address'],
                'address'            => $address,
                'notes'              => $row['notes'],
                'birthday'           => $row['birthday'],
                'last_appointment'   => $row['last_appointment'] ? Lib\Utils\DateTime::formatDateTime( $row['last_appointment'] ) : '',
                'total_appointments' => $row['total_appointments'],
                'payments'           => Lib\Utils\Price::format( $row['payments'] ),
            );

            $customer_data = Proxy\CustomerGroups::prepareCustomerListData( $customer_data, $row );
            $customer_data = Proxy\CustomerInformation::prepareCustomerListData( $customer_data, $row );

            $data[] = $customer_data;
        }

        wp_send_json( array(
            'draw'            => ( int ) self::parameter( 'draw' ),
            'recordsTotal'    => $total,
            'recordsFiltered' => ( int ) $wpdb->get_var( 'SELECT FOUND_ROWS()' ),
            'data'            => $data,
        ) );
    }

    /**
     * Export Customers to CSV
     */
    public static function exportCustomers()
    {
        global $wpdb;
        $delimiter = self::parameter( 'export_customers_delimiter', ',' );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=Customers.csv' );

        $titles = array(
            'full_name'          => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_name' ),
            'first_name'         => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_first_name' ),
            'last_name'          => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_last_name' ),
            'wp_user'            => __( 'User', 'bookly' )
        );

        $titles = Proxy\CustomerGroups::prepareCustomerExportTitles( $titles );

        $titles = array_merge( $titles, array(
            'phone'              => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_phone' ),
            'email'              => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_email' ),
            'address'            => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_info_address' ),
            'notes'              => __( 'Notes', 'bookly' ),
            'last_appointment'   => __( 'Last appointment', 'bookly' ),
            'total_appointments' => __( 'Total appointments', 'bookly' ),
            'payments'           => __( 'Payments', 'bookly' ),
            'birthday'           => __( 'Date of birth', 'bookly' ),
        ) );

        $fields = (array) Lib\Proxy\CustomerInformation::getFields();

        foreach ( $fields as $field ) {
            $titles[ $field->id ] = $field->label;
        }

        $header = array();
        $column = array();

        foreach ( self::parameter( 'exp', array() ) as $key => $value ) {
            $header[] = $titles[ $key ];
            $column[] = $key;
        }

        $output = fopen( 'php://output', 'w' );
        fwrite( $output, pack( 'CCC', 0xef, 0xbb, 0xbf ) );
        fputcsv( $output, $header, $delimiter );

        $select = 'c.*, MAX(a.start_date) AS last_appointment,
                COUNT(a.id) AS total_appointments,
                COALESCE(SUM(p.total),0) AS payments,
                wpu.display_name AS wp_user';
        $select = Proxy\CustomerGroups::prepareCustomerSelect( $select );

        $query = Lib\Entities\Customer::query( 'c' )
            ->select( $select )
            ->leftJoin( 'CustomerAppointment', 'ca', 'ca.customer_id = c.id' )
            ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
            ->leftJoin( 'Payment', 'p', 'p.id = ca.payment_id' )
            ->tableJoin( $wpdb->users, 'wpu', 'wpu.ID = c.wp_user_id' )
            ->groupBy( 'c.id' );

        $query = Proxy\CustomerGroups::prepareCustomerQuery( $query );

        $rows = $query->fetchArray();

        foreach ( $rows as $row ) {
            $row_data = array_fill( 0, count( $column ), '' );
            foreach ( $row as $key => $value ) {
                if ( $key == 'info_fields' ) {
                    foreach ( json_decode( $value ) as $field ) {
                        $pos = array_search( $field->id, $column );
                        if ( $pos !== false ) {
                            $row_data[ $pos ] = is_array( $field->value ) ? implode( ', ', $field->value ) : $field->value;
                        }
                    }
                } else {
                    $pos = array_search( $key, $column );
                    if ( $pos !== false ) {
                        $row_data[ $pos ] = $value;
                    }
                }
            }

            $pos = array_search( 'address', $column );
            if ( $pos !== false ) {
                $fullAddress = Lib\Utils\Common::getFullAddressByCustomerData( $row );
                $row_data[ $pos ] = $fullAddress;
            }

            fputcsv( $output, $row_data, $delimiter );
        }

        fclose( $output );

        exit;
    }

    /**
     * Delete customers.
     */
    public static function deleteCustomers()
    {
        foreach ( self::parameter( 'data', array() ) as $id ) {
            $customer = new Lib\Entities\Customer();
            $customer->load( $id );
            $customer->deleteWithWPUser( (bool) self::parameter( 'with_wp_user' ) );
        }
        wp_send_json_success();
    }

    /**
     * Merge customers.
     */
    public static function mergeCustomers()
    {
        $target_id = self::parameter( 'target_id' );
        $ids       = self::parameter( 'ids', array() );

        // Move appointments.
        Lib\Entities\CustomerAppointment::query()
            ->update()
            ->set( 'customer_id', $target_id )
            ->whereIn( 'customer_id', $ids )
            ->execute();

        // Let add-ons do their stuff.
        Proxy\Shared::mergeCustomers( $target_id, $ids );

        // Merge customer data.
        $target_customer = Lib\Entities\Customer::find( $target_id );
        foreach ( $ids as $id ) {
            if ( $id != $target_id ) {
                $customer = Lib\Entities\Customer::find( $id );
                if ( ! $target_customer->getWpUserId() && $customer->getWpUserId() ) {
                    $target_customer->setWpUserId( $customer->getWpUserId() );
                }
                if ( ! $target_customer->getGroupId() ) {
                    $target_customer->setGroupId( $customer->getGroupId() );
                }
                if ( ! $target_customer->getFacebookId() ) {
                    $target_customer->setFacebookId( $customer->getFacebookId() );
                }
                if ( $target_customer->getFullName() == '' ) {
                    $target_customer->setFullName( $customer->getFullName() );
                }
                if ( $target_customer->getFirstName() == '' ) {
                    $target_customer->setFirstName( $customer->getFirstName() );
                }
                if ( $target_customer->getLastName() == '' ) {
                    $target_customer->setLastName( $customer->getLastName() );
                }
                if ( $target_customer->getPhone() == '' ) {
                    $target_customer->setPhone( $customer->getPhone() );
                }
                if ( $target_customer->getEmail() == '' ) {
                    $target_customer->setEmail( $customer->getEmail() );
                }
                if ( $target_customer->getBirthday() == '' ) {
                    $target_customer->setBirthday( $customer->getBirthday() );
                }
                if ( $target_customer->getCountry() == '' ) {
                    $target_customer->setCountry( $customer->getCountry() );
                }
                if ( $target_customer->getState() == '' ) {
                    $target_customer->setState( $customer->getState() );
                }
                if ( $target_customer->getPostcode() == '' ) {
                    $target_customer->setPostcode( $customer->getPostcode() );
                }
                if ( $target_customer->getCity() == '' ) {
                    $target_customer->setCity( $customer->getCity() );
                }
                if ( $target_customer->getStreet() == '' ) {
                    $target_customer->setStreet( $customer->getStreet() );
                }
                if ( $target_customer->getAdditionalAddress() == '' ) {
                    $target_customer->setAdditionalAddress( $customer->getAdditionalAddress() );
                }
                if ( $target_customer->getNotes() == '' ) {
                    $target_customer->setNotes( $customer->getNotes() );
                }
                // Delete merged customer.
                $customer->delete();
            }
            $target_customer->save();
        }

        wp_send_json_success();
    }

    /**
     * Check if the current user has access to the action.
     *
     * @param string $action
     * @return bool
     */
    protected static function hasAccess( $action )
    {
        if ( parent::hasAccess( $action ) ) {
            if ( ! Lib\Utils\Common::isCurrentUserAdmin() ) {
                switch ( $action ) {
                    case 'saveCustomer':
                    case 'getCustomers':
                    case 'exportCustomers':
                    case 'deleteCustomers':
                        return Lib\Entities\Staff::query()
                            ->where( 'wp_user_id', get_current_user_id() )
                            ->count() > 0;
                }
            } else {
                return true;
            }
        }

        return false;
    }
}