<?php
namespace BooklyCart\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyCart\Lib;
use BooklyCart\Backend\Modules as Backend;
use BooklyCart\Frontend\Modules as Frontend;

/**
 * Class Shared
 * Provides shared methods to be used in Bookly.
 *
 * @package BooklyCart\Lib\ProxyProviders
 */
abstract class Shared extends BooklyLib\Base\ProxyProvider
{
    /**
     * Prepare appearance options.
     *
     * @param array $options_to_save
     * @param array $options
     * @return array
     */
    public static function prepareAppearanceOptions( array $options_to_save, array $options )
    {
        if ( Lib\Plugin::enabled() ) {
            $options_to_save = array_merge( $options_to_save, array_intersect_key( $options, array_flip( array (
                'bookly_l10n_button_book_more',
                'bookly_l10n_info_cart_step',
                'bookly_l10n_step_cart',
                'bookly_l10n_step_cart_button_next',
                'bookly_l10n_step_cart_slot_not_available',
            ) ) ) );
        }

        return $options_to_save;
    }

    /**
     * Render Cart settings in Bookly Settings.
     *
     * @throws
     */
    public static function renderSettingsForm()
    {
        Backend\Settings\Components::getInstance()->renderSettingsForm();
    }

    /**
     * Render Cart menu in Bookly Settings.
     */
    public static function renderSettingsMenu()
    {
        printf( '<li class="bookly-nav-item" data-target="#bookly_settings_cart" data-toggle="tab">%s</li>', __( 'Cart', 'bookly-cart' ) );
    }

    /**
     * Save settings.
     *
     * @param array  $alert
     * @param string $tab
     * @param array  $_post
     * @return array
     */
    public static function saveSettings( array $alert, $tab, $_post )
    {
        if ( $tab == 'cart' && ! empty( $_post ) ) {
            $options = array( 'bookly_cart_enabled', 'bookly_cart_show_columns' );
            foreach ( $options as $option_name ) {
                if ( array_key_exists( $option_name, $_post ) ) {
                    update_option( $option_name, $_post[ $option_name ] );
                }
            }
            $alert['success'][] = __( 'Settings saved.', 'bookly-cart' );
            if ( get_option( 'bookly_wc_enabled' ) && $_post['bookly_cart_enabled'] ) {
                $alert['error'][] = sprintf(
                    __( 'To use the cart, disable integration with WooCommerce <a href="%s">here</a>.', 'bookly-cart' ),
                    BooklyLib\Utils\Common::escAdminUrl( \Bookly\Backend\Modules\Settings\Controller::page_slug, array( 'tab' => 'woocommerce' ) )
                );
            }
        }

        return $alert;
    }

}