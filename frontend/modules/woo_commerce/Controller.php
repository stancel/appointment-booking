<?php
namespace Bookly\Frontend\Modules\WooCommerce;

use Bookly\Lib;
use Bookly\Frontend\Modules\Booking\Proxy;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\WooCommerce
 */
class Controller extends Lib\Base\Ajax
{
    const VERSION = '1.0';

    protected static $product_id = 0;
    protected static $checkout_info = array();

    /**
     * @inheritdoc
     */
    public static function init()
    {
        if ( get_option( 'bookly_wc_enabled' ) ) {
            $self = get_called_class();
            self::$product_id = get_option( 'bookly_wc_product', 0 );
            // WC 3.0 woocommerce_add_order_item_meta is deprecated but
            // new action wc_add_order_item_meta is missing.
            add_action( 'woocommerce_add_order_item_meta',      array( $self, 'addOrderItemMeta' ), 10, 3 );
            // WC 3.1.1 missing registration wc_add_order_item_meta
            // add_action( 'wc_add_order_item_meta',            array( $this, 'addOrderItemMeta' ), 10, 3 );
            add_action( 'woocommerce_after_order_itemmeta',     array( $self, 'orderItemMeta' ), 10, 1 );
            add_action( 'woocommerce_before_calculate_totals',  array( $self, 'beforeCalculateTotals' ), 10, 1 );
            add_action( 'woocommerce_before_cart_contents',     array( $self, 'checkAvailableTimeForCart' ), 10, 0 );
            add_action( 'woocommerce_order_item_meta_end',      array( $self, 'orderItemMeta' ), 10, 1 );
            add_action( 'woocommerce_order_status_cancelled',   array( $self, 'cancelOrder' ), 10, 1 );
            add_action( 'woocommerce_order_status_completed',   array( $self, 'paymentComplete' ), 10, 1 );
            add_action( 'woocommerce_order_status_on-hold',     array( $self, 'paymentComplete' ), 10, 1 );
            add_action( 'woocommerce_order_status_processing',  array( $self, 'paymentComplete' ), 10, 1 );
            add_action( 'woocommerce_order_status_refunded',    array( $self, 'cancelOrder' ), 10, 1 );

            add_filter( 'woocommerce_checkout_get_value',       array( $self, 'checkoutValue' ), 10, 2 );
            add_filter( 'woocommerce_get_item_data',            array( $self, 'getItemData' ), 10, 2 );
            add_filter( 'woocommerce_quantity_input_args',      array( $self, 'quantityArgs' ), 10, 2 );
            add_filter( 'woocommerce_cart_item_price',          array( $self, 'getCartItemPrice' ), 10, 3 );

            parent::init();
        }
    }

    /**
     * Verifies the availability of all appointments that are in the cart
     */
    public static function checkAvailableTimeForCart()
    {
        $recalculate_totals = false;
        foreach ( WC()->cart->get_cart() as $wc_key => $wc_item ) {
            if ( array_key_exists( 'bookly', $wc_item ) ) {
                if ( ! isset( $wc_item['bookly']['version'] ) ) {
                    if ( self::_migration( $wc_key, $wc_item ) === false ) {
                        // Removed item from cart.
                        continue;
                    }
                }
                $userData = new Lib\UserBookingData( null );
                $userData->fillData( $wc_item['bookly'] );
                $userData->cart->setItemsData( $wc_item['bookly']['items'] );
                if ( $wc_item['quantity'] > 1 ) {
                    foreach ( $userData->cart->getItems() as $cart_item ) {
                        // Equal appointments increase quantity
                        $cart_item->setNumberOfPersons( $cart_item->getNumberOfPersons() * $wc_item['quantity'] );
                    }
                }
                // Check if appointment's time is still available
                $failed_cart_key = $userData->cart->getFailedKey();
                if ( $failed_cart_key !== null ) {
                    $cart_item = $userData->cart->get( $failed_cart_key );
                    $slot = $cart_item->getSlots();
                    $notice = strtr( __( 'Sorry, the time slot %date_time% for %service% has been already occupied.', 'bookly' ),
                        array(
                            '%service%'   => '<strong>' . $cart_item->getService()->getTranslatedTitle() . '</strong>',
                            '%date_time%' => Lib\Utils\DateTime::formatDateTime( $slot[0][2] )
                    ) );
                    wc_print_notice( $notice, 'notice' );
                    WC()->cart->set_quantity( $wc_key, 0, false );
                    $recalculate_totals = true;
                }
            }
        }
        if ( $recalculate_totals ) {
            WC()->cart->calculate_totals();
        }
    }

    /**
     * Assign checkout value from appointment.
     *
     * @param $null
     * @param $field_name
     * @return string|null
     */
    public static function checkoutValue( $null, $field_name )
    {
        if ( empty( self::$checkout_info ) ) {
            foreach ( WC()->cart->get_cart() as $wc_key => $wc_item ) {
                if ( array_key_exists( 'bookly', $wc_item ) ) {
                    if ( ! isset( $wc_item['bookly']['version'] ) || $wc_item['bookly']['version'] < self::VERSION ) {
                        if ( self::_migration( $wc_key, $wc_item ) === false ) {
                            // Removed item from cart.
                            continue;
                        }
                    }
                    self::$checkout_info = array(
                        'billing_first_name' => $wc_item['bookly']['first_name'],
                        'billing_last_name'  => $wc_item['bookly']['last_name'],
                        'billing_email'      => $wc_item['bookly']['email'],
                        'billing_phone'      => $wc_item['bookly']['phone']
                    );
                    break;
                }
            }
        }
        if ( array_key_exists( $field_name, self::$checkout_info ) ) {
            return self::$checkout_info[ $field_name ];
        }

        return null;
    }

    /**
     * Do bookings after checkout.
     *
     * @param $order_id
     */
    public static function paymentComplete( $order_id )
    {
        $order = new \WC_Order( $order_id );
        foreach ( $order->get_items() as $item_id => $order_item ) {
            $data = wc_get_order_item_meta( $item_id, 'bookly' );
            if ( $data && ! isset ( $data['processed'] ) ) {
                $userData = new Lib\UserBookingData( null );
                $userData->fillData( $data );
                $userData->cart->setItemsData( $data['items'] );
                if ( $order_item['qty'] > 1 ) {
                    foreach ( $userData->cart->getItems() as $cart_item ) {
                        $cart_item->setNumberOfPersons( $cart_item->getNumberOfPersons() * $order_item['qty'] );
                    }
                }
                $cart_info = $userData->cart->getInfo();
                $payment = new Lib\Entities\Payment();
                $payment
                    ->setType( Lib\Entities\Payment::TYPE_WOOCOMMERCE )
                    ->setStatus( Lib\Entities\Payment::STATUS_COMPLETED )
                    ->setCartInfo( $cart_info )
                    ->save();
                $order = $userData->save( $payment );
                $payment->setDetailsFromOrder( $order, $cart_info )->save();
                if ( get_option( 'bookly_cst_create_account' ) && $order->getCustomer()->getWpUserId() ) {
                    update_post_meta( $order_id, '_customer_user', $order->getCustomer()->getWpUserId() );
                }
                // Mark item as processed.
                $data['processed'] = true;
                $data['ca_list']   = array();
                foreach ( $order->getFlatItems() as $item ) {
                    $data['ca_list'][] = $item->getCA()->getId();
                }
                wc_update_order_item_meta( $item_id, 'bookly', $data );
                Lib\NotificationSender::sendFromCart( $order );
            }
        }
    }

    /**
     * Cancel appointments on WC order cancelled.
     *
     * @param $order_id
     */
    public static function cancelOrder( $order_id )
    {
        $order = new \WC_Order( $order_id );
        foreach ( $order->get_items() as $item_id => $order_item ) {
            $data = wc_get_order_item_meta( $item_id, 'bookly' );
            if ( isset ( $data['processed'], $data['ca_ids'] ) && $data['processed'] ) {
                /** @var Lib\Entities\CustomerAppointment[] $ca_list */
                $ca_list = Lib\Entities\CustomerAppointment::query()->whereIn( 'id', $data['ca_ids'] )->find();
                foreach ( $ca_list as $ca ) {
                    $ca->cancel();
                }
                $data['ca_ids'] = array();
                wc_update_order_item_meta( $item_id, 'bookly', $data );
            }
        }
    }

    /**
     * Change attr for WC quantity input
     *
     * @param array       $args
     * @param \WC_Product $product
     * @return mixed
     */
    public static function quantityArgs( $args, $product )
    {
        if ( $product->get_id() == self::$product_id ) {
            $args['max_value'] = $args['input_value'];
            $args['min_value'] = $args['input_value'];
        }

        return $args;
    }

    /**
     * Change item price in cart.
     *
     * @param \WC_Cart $cart_object
     */
    public static function beforeCalculateTotals( $cart_object )
    {
        foreach ( $cart_object->cart_contents as $wc_key => $wc_item ) {
            if ( isset ( $wc_item['bookly'] ) ) {
                if ( ! isset( $wc_item['bookly']['version'] ) || $wc_item['bookly']['version'] < self::VERSION ) {
                    if ( self::_migration( $wc_key, $wc_item ) === false ) {
                        // Removed item from cart.
                        continue;
                    }
                }
                $userData = new Lib\UserBookingData( null );
                $userData->fillData( $wc_item['bookly'] );
                $userData->cart->setItemsData( $wc_item['bookly']['items'] );
                $cart_info = $userData->cart->getInfo();
                /** @var \WC_Product $wc_item['data'] */
                $wc_item['data']->set_price( $cart_info->getPayNow() );
            }
        }
    }

    /**
     * Add order item meta.
     *
     * @param $item_id
     * @param $values
     * @param $wc_key
     */
    public static function addOrderItemMeta( $item_id, $values, $wc_key )
    {
        if ( isset ( $values['bookly'] ) ) {
            wc_update_order_item_meta( $item_id, 'bookly', $values['bookly'] );
        }
    }

    /**
     * Get item data for cart.
     *
     * @param $other_data
     * @param $wc_item
     * @return array
     */
    public static function getItemData( $other_data, $wc_item )
    {
        if ( isset ( $wc_item['bookly'] ) ) {
            $userData = new Lib\UserBookingData( null );
            $info = array();
            if ( isset ( $wc_item['bookly']['version'] ) && $wc_item['bookly']['version'] == self::VERSION ) {
                $userData->fillData( $wc_item['bookly'] );
                if ( Lib\Config::useClientTimeZone() ) {
                    $userData->applyTimeZone();
                }
                $userData->cart->setItemsData( $wc_item['bookly']['items'] );
                $cart_info = $userData->cart->getInfo();
                foreach ( $userData->cart->getItems() as $cart_item ) {
                    $slots     = $cart_item->getSlots();
                    $client_dp = Lib\Slots\DatePoint::fromStr( $slots[0][2] )->toClientTz();
                    $service   = $cart_item->getService();
                    $staff     = $cart_item->getStaff();
                    $codes = array(
                        '{amount_due}'        => Lib\Utils\Price::format( $cart_info->getDue() ),
                        '{amount_to_pay}'     => Lib\Utils\Price::format( $cart_info->getPayNow() ),
                        '{appointment_date}'  => $client_dp->formatI18nDate(),
                        '{appointment_time}'  => $client_dp->formatI18nTime(),
                        '{category_name}'     => $service ? $service->getTranslatedCategoryName() : '',
                        '{deposit_value}'     => Lib\Utils\Price::format( $cart_info->getDepositPay() ),
                        '{number_of_persons}' => $cart_item->getNumberOfPersons(),
                        '{service_info}'      => $service ? $service->getTranslatedInfo() : '',
                        '{service_name}'      => $service ? $service->getTranslatedTitle() : __( 'Service was not found', 'bookly' ),
                        '{service_price}'     => $service ? Lib\Utils\Price::format( $cart_item->getServicePrice() ) : '',
                        '{staff_info}'        => $staff ? $staff->getTranslatedInfo() : '',
                        '{staff_name}'        => $staff ? $staff->getTranslatedName() : '',
                    );
                    $data  = Proxy\Shared::prepareCartItemInfoText( array(), $cart_item );
                    $codes = Proxy\Shared::prepareInfoTextCodes( $codes, $data );
                    // Support deprecated codes [[CODE]]
                    foreach ( array_keys( $codes ) as $code_key ) {
                        if ( $code_key{1} == '[' ) {
                            $codes[ '{' . strtolower( substr( $code_key, 2, -2 ) ) . '}' ] = $codes[ $code_key ];
                        } else {
                            $codes[ '[[' . strtoupper( substr( $code_key, 1, -1 ) ) . ']]' ] = $codes[ $code_key ];
                        }
                    }
                    $info[]  = strtr( Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_wc_cart_info_value' ), $codes );
                }
            }
            $other_data[] = array( 'name' => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_wc_cart_info_name' ), 'value' => implode( PHP_EOL . PHP_EOL, $info ) );
        }

        return $other_data;
    }

    /**
     * Print appointment details inside order items in the backend.
     *
     * @param int $item_id
     */
    public static function orderItemMeta( $item_id )
    {
        $data = wc_get_order_item_meta( $item_id, 'bookly' );
        if ( $data ) {
            $other_data = self::getItemData( array(), array( 'bookly' => $data ) );
            echo '<br/>' . $other_data[0]['name'] . '<br/>' . nl2br( $other_data[0]['value'] );
        }
    }

    /**
     * Get cart item price.
     *
     * @param $product_price
     * @param $wc_item
     * @param $cart_item_key
     * @return mixed
     */
    public static function getCartItemPrice( $product_price, $wc_item, $cart_item_key )
    {
        if ( isset ( $wc_item['bookly'] ) ) {
            $userData = new Lib\UserBookingData( null );
            $userData->fillData( $wc_item['bookly'] );
            $userData->cart->setItemsData( $wc_item['bookly']['items'] );
            $product_price = wc_price( $userData->cart->getInfo()->getPayNow() );
        }

        return $product_price;
    }

    /**
     * Migration deprecated cart items.
     *
     * @param $wc_key
     * @param $data
     * @return bool
     */
    protected static function _migration( $wc_key, $data )
    {
        // The current implementation only remove cart items with deprecated format.
        WC()->cart->set_quantity( $wc_key, 0, false );
        WC()->cart->calculate_totals();

        return false;
    }
}