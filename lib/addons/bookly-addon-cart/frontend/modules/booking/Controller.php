<?php
namespace BooklyCart\Frontend\Modules\Booking;

use Bookly\Lib as BooklyLib;

/**
 * Class Controller
 * @package BooklyCart\Frontend\Modules\Booking
 */
class Controller extends BooklyLib\Base\Controller
{
    /**
     * Render Cart step on Frontend.
     *
     * @param BooklyLib\UserBookingData $userData
     * @param string $progress_tracker
     * @param string $info_text
     * @return string
     * @throws
     */
    public function renderStep( BooklyLib\UserBookingData $userData, $progress_tracker, $info_text )
    {
        $table = array(
            'headers'    => array(),
            'header_position' => array(),
            'rows'       => array(),
            'show'       => array(
                'deposit' => false,
                'tax'     => false,
            ),
        );
        $cart_columns = get_option( 'bookly_cart_show_columns', array() );

        foreach ( $userData->cart->getItems() as $cart_key => $cart_item ) {
            if ( BooklyLib\Proxy\RecurringAppointments::hideChildAppointments( false, $cart_item ) ) {
                continue;
            }
            $nop_prefix = ( $cart_item->getNumberOfPersons() > 1 ? '<i class="bookly-icon-user"></i>' . $cart_item->getNumberOfPersons() . ' &times; ' : '' );
            $slots      = $cart_item->getSlots();
            $service_dp = BooklyLib\Slots\DatePoint::fromStr( $slots[0][2] )->toClientTz();

            foreach ( $cart_columns as $header => $attr ) {
                if ( $attr['show'] ) {
                    switch ( $header ) {
                        case 'service':
                            $table['rows'][ $cart_key ][] = $cart_item->getService()->getTranslatedTitle();
                            break;
                        case 'date':
                            $table['rows'][ $cart_key ][] = $service_dp->formatI18nDate();
                            break;
                        case 'time':
                            if ( $cart_item->getService()->getDuration() < DAY_IN_SECONDS ) {
                                $table['rows'][ $cart_key ][] = $service_dp->formatI18nTime();
                            } else {
                                $table['rows'][ $cart_key ][] = '';
                            }
                            break;
                        case 'employee':
                            $table['rows'][ $cart_key ][] = $cart_item->getStaff()->getTranslatedName();
                            break;
                        case 'price':
                            if ( $cart_item->getNumberOfPersons() > 1 ) {
                                $table['rows'][ $cart_key ][] = $nop_prefix . BooklyLib\Utils\Price::format( $cart_item->getServicePriceWithoutExtras() ) . ' = ' . BooklyLib\Utils\Price::format( $cart_item->getServicePriceWithoutExtras() * $cart_item->getNumberOfPersons() );
                            } else {
                                $table['rows'][ $cart_key ][] = BooklyLib\Utils\Price::format( $cart_item->getServicePriceWithoutExtras() );
                            }
                            break;
                        case 'deposit':
                            if ( BooklyLib\Config::depositPaymentsEnabled() ) {
                                $table['rows'][ $cart_key ][] = BooklyLib\Proxy\DepositPayments::formatDeposit( $cart_item->getDepositPrice(), $cart_item->getDeposit() );
                                $table['show']['deposit'] = true;
                            }
                            break;
                        case 'tax':
                            if ( BooklyLib\Config::taxesEnabled() ) {
                                $tax = '';
                                if ( ! $cart_item->toBePutOnWaitingList() ){
                                    $tax = BooklyLib\Utils\Price::format( BooklyLib\Proxy\Taxes::getAmountOfTax( $cart_item ) );
                                }
                                $table['rows'][ $cart_key ][] = $tax;
                                $table['show']['tax'] = true;
                            }
                            break;
                    }
                }
            }
        }

        $position = 0;
        foreach ( $cart_columns as $header => $attr ) {
            if ( $attr['show'] ) {
                if ( $header != 'deposit' || $table['show']['deposit'] ) {
                    $table['header_position'][ $header ] = $position;
                }
                switch ( $header ) {
                    case 'service':
                        $table['headers'][] = BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_service' );
                        ++ $position;
                        break;
                    case 'date':
                        $table['headers'][] = __( 'Date', 'bookly-cart' );
                        ++ $position;
                        break;
                    case 'time':
                        $table['headers'][] = __( 'Time', 'bookly-cart' );
                        ++ $position;
                        break;
                    case 'employee':
                        $table['headers'][] = BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_employee' );
                        ++ $position;
                        break;
                    case 'price':
                        $table['headers'][] = __( 'Price', 'bookly-cart' );
                        ++ $position;
                        break;
                    case 'deposit':
                        if ( $table['show']['deposit'] ) {
                            $table['headers'][] = __( 'Deposit', 'bookly-cart' );
                            ++ $position;
                        }
                        break;
                    case 'tax':
                        if ( $table['show']['tax'] ) {
                            $table['headers'][] = __( 'Tax', 'bookly-cart' );
                            ++ $position;
                        }
                        break;
                }
            }
        }
        $cart_info = $userData->cart->getInfo( false ); // without coupon

        return $this->render( '5_cart', array(
                'progress_tracker' => $progress_tracker,
                'info_text'        => $info_text,
                'userData'         => $userData,
                'table'            => $table,
                'cart_info'        => $cart_info,
            ), false );
    }
}