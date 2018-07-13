<?php
namespace Bookly\Frontend\Modules\WooCommerce;

use Bookly\Lib;
use Bookly\Frontend\Modules\Booking\Lib\Errors;

/**
 * Class Ajax
 * @package Bookly\Frontend\Modules\WooCommerce
 */
class Ajax extends Controller
{
    /**
     * @inheritdoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'anonymous' );
    }

    /**
     * Add product to cart
     *
     * return string JSON
     */
    public static function addToWoocommerceCart()
    {
        if ( ! get_option( 'bookly_wc_enabled' ) ) {
            exit;
        }
        $response = null;
        $userData = new Lib\UserBookingData( self::parameter( 'form_id' ) );

        if ( $userData->load() ) {
            $session = WC()->session;
            /** @var \WC_Session_Handler $session */
            if ( $session instanceof \WC_Session_Handler && $session->get_session_cookie() === false ) {
                $session->set_customer_session_cookie( true );
            }
            if ( $userData->cart->getFailedKey() === null ) {
                $cart_item  = self::_getIntersectedItem( $userData->cart->getItems() );
                if ( $cart_item === null ) {
                    $first_name = $userData->getFirstName();
                    $last_name  = $userData->getLastName();
                    $full_name  = $userData->getFullName();
                    // Check if defined First name
                    if ( ! $first_name ) {
                        $first_name = strtok( $full_name, ' ' );
                        $last_name  = strtok( '' );
                    }
                    $bookly = array(
                        'version'           => self::VERSION,
                        'email'             => $userData->getEmail(),
                        'items'             => $userData->cart->getItemsData(),
                        'full_name'         => $full_name,
                        'first_name'        => $first_name,
                        'last_name'         => $last_name,
                        'phone'             => $userData->getPhone(),
                        'time_zone'         => $userData->getTimeZone(),
                        'time_zone_offset'  => $userData->getTimeZoneOffset(),
                        'notes'             => $userData->getNotes()
                    );

                    // Qnt 1 product in $userData exists value with number_of_persons
                    WC()->cart->add_to_cart( self::$product_id, 1, '', array(), array( 'bookly' => $bookly ) );

                    $response = array( 'success' => true );
                } else {
                    $response = array( 'success' => false, 'error' => Errors::CART_ITEM_NOT_AVAILABLE );
                }
            } else {
                $response = array( 'success' => false, 'error' => Errors::CART_ITEM_NOT_AVAILABLE );
            }
        } else {
            $response = array( 'success' => false, 'error' => Errors::SESSION_ERROR );
        }
        wp_send_json( $response );
    }

    /**
     * Find conflicted CartItem with items in WC Cart.
     *
     * Resolved:
     *  number of persons > staff.capacity
     *  Services for some Staff intersected
     *
     * @param Lib\CartItem[] $new_items
     * @return Lib\CartItem
     */
    private static function _getIntersectedItem( array $new_items )
    {
        /** @var Lib\CartItem[] $wc_items */
        $wc_items  = array();
        $cart_item = new Lib\CartItem();
        foreach ( WC()->cart->get_cart() as $wc_key => $wc_item ) {
            if ( array_key_exists( 'bookly', $wc_item ) ) {
                if ( ! isset( $wc_item['bookly']['version'] ) ) {
                    if ( self::_migration( $wc_key, $wc_item ) === false ) {
                        // Removed item from cart.
                        continue;
                    }
                }
                foreach ( $wc_item['bookly']['items'] as $item_data ) {
                    $entity = clone $cart_item;
                    $entity->setData( $item_data );
                    if ( $wc_item['quantity'] > 1 ) {
                        $nop = $item_data['number_of_persons'] *= $wc_item['quantity'];
                        $entity->setNumberOfPersons( $nop );
                    }
                    $wc_items[] = $entity;
                }
            }
        }
        $staff_service = array();
        foreach ( $new_items as $cart_key => $candidate_cart_item ) {
            foreach ( $wc_items as $wc_cart_item ) {
                $candidate_staff_id   = $candidate_cart_item->getStaff()->getId();
                $candidate_service_id = $candidate_cart_item->getService()->getId();
                $candidate_slots      = $candidate_cart_item->getSlots();
                $wc_cart_slots        = $wc_cart_item->getSlots();
                if ( $wc_cart_item->getStaff() && $candidate_cart_item->getService() ) {
                    if ( $candidate_staff_id == $wc_cart_item->getStaff()->getId() ) {
                        // Equal Staff
                        $candidate_start = date_create( $candidate_slots[0][2] );
                        $candidate_end   = date_create( $candidate_slots[0][2] )->modify( ( $candidate_cart_item->getService()->getDuration() + $candidate_cart_item->getExtrasDuration() ) . ' sec' );
                        $wc_cart_start   = date_create( $wc_cart_slots[0][2] );
                        $wc_cart_end     = date_create( $wc_cart_slots[0][2] )->modify( ( $wc_cart_item->getService()->getDuration() + $wc_cart_item->getExtrasDuration() ) . ' sec' );
                        if ( ( $wc_cart_end > $candidate_start ) && ( $candidate_end > $wc_cart_start ) ) {
                            // Services intersected.
                            if ( $candidate_start == $wc_cart_start ) {
                                // Equal Staff/Service/Start
                                if ( ! isset( $staff_service[ $candidate_staff_id ][ $candidate_service_id ] ) ) {
                                    $staff_service[ $candidate_staff_id ][ $candidate_service_id ] = self::_getCapacity( $candidate_staff_id, $candidate_service_id );
                                }
                                $allow_capacity = $staff_service[ $candidate_staff_id ][ $candidate_service_id ];
                                $nop = $candidate_cart_item->getNumberOfPersons() + $wc_cart_item->getNumberOfPersons();
                                if ( $nop > $allow_capacity ) {
                                    // Equal Staff/Service/Start and number_of_persons > capacity
                                    return $candidate_cart_item;
                                }
                            } else {
                                // Intersect Services for some Staff
                                return $candidate_cart_item;
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get capacity.
     *
     * @param int $staff_id
     * @param int $service_id
     * @return int
     */
    private static function _getCapacity( $staff_id, $service_id )
    {
        $staff_service = Lib\Entities\StaffService::query()
            ->select( 'capacity_max' )
            ->where( 'staff_id',   $staff_id )
            ->where( 'service_id', $service_id )
            ->fetchRow();

        return $staff_service['capacity_max'];
    }
}