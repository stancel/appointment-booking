<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*
Plugin Name: Bookly Cart (Add-on)
Plugin URI: http://booking-wp-plugin.com
Description: Bookly Cart add-on allows your clients to book several appointments per session by placing them in a cart.
Version: 1.0
Author: Ladela Interactive
Author URI: http://booking-wp-plugin.com
Text Domain: bookly-cart
Domain Path: /languages
License: Commercial
*/

if ( ! function_exists( 'bookly_cart_loader' ) ) {
    include_once __DIR__ . '/autoload.php';

    if ( class_exists( '\Bookly\Lib\Plugin' ) && version_compare( Bookly\Lib\Plugin::getVersion(), '14.9', '>=' ) ) {
        BooklyCart\Lib\Plugin::run();
    } else {
        add_action( 'init', function () {
            if ( current_user_can( 'activate_plugins' ) ) {
                add_action( 'admin_init', function () {
                    deactivate_plugins( 'bookly-addon-cart/main.php', false, is_network_admin() );
                } );
                add_action( is_network_admin() ? 'network_admin_notices' : 'admin_notices', function () {
                    printf( '<div class="updated"><h3>Bookly Cart (Add-on)</h3><p>The plugin has been <strong>deactivated</strong>.</p><p><strong>Bookly v%s</strong> is required.</p></div>',
                        '14.9'
                    );
                } );
                unset ( $_GET['activate'], $_GET['activate-multi'] );
            }
        } );
    }
}