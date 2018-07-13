<?php
namespace Bookly\Frontend;

use Bookly\Lib;

/**
 * Class Frontend
 * @package Bookly\Frontend
 */
class Frontend
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action( 'wp_loaded', array( $this, 'init' ) );

        // Init components.
        Modules\Booking\Ajax::init();
        Modules\Booking\ShortCode::init();
        Modules\CustomerProfile\Ajax::init();
        Modules\WooCommerce\Ajax::init();

        // Register shortcodes.
        add_shortcode( 'bookly-form', function ( $attrs ) { return Modules\Booking\ShortCode::generate( $attrs ); } );
        /** @deprecated [ap-booking] */
        add_shortcode( 'ap-booking', function ( $attrs ) { return Modules\Booking\ShortCode::generate( $attrs ); } );
        add_shortcode( 'bookly-appointments-list', function ( $attrs ) { return Modules\CustomerProfile\ShortCode::generate( $attrs ); } );
        add_shortcode( 'bookly-cancellation-confirmation', function ( $attrs ) { return Modules\CancellationConfirmation\ShortCode::generate( $attrs ); } );
    }

    /**
     * Init.
     */
    public function init()
    {
        if ( ! session_id() ) {
            @session_start();
        }

        // Payments ( PayPal Express Checkout and etc. )
        if ( isset( $_REQUEST['bookly_action'] ) ) {
            // Disable caching.
            Lib\Utils\Common::noCache();

            switch ( $_REQUEST['bookly_action'] ) {
                // PayPal Express Checkout.
                case 'paypal-ec-init':
                    Modules\Paypal\Controller::ecInit();
                    break;
                case 'paypal-ec-return':
                    Modules\Paypal\Controller::ecReturn();
                    break;
                case 'paypal-ec-cancel':
                    Modules\Paypal\Controller::ecCancel();
                    break;
                case 'paypal-ec-error':
                    Modules\Paypal\Controller::ecError();
                    break;
                default:
                    Lib\Proxy\Shared::handleRequestAction( $_REQUEST['bookly_action'] );
            }
        }
    }

}