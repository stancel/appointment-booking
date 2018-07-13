<?php
namespace Bookly\Frontend\Modules\Booking;

use Bookly\Lib;
use Bookly\Frontend\Modules\Booking\Lib\Errors;

/**
 * Class ShortCode
 * @package Bookly\Frontend\Modules\Booking
 */
class ShortCode extends Lib\Base\Component
{
    /**
     * Init component.
     */
    public static function init()
    {
        add_action(
            get_option( 'bookly_gen_link_assets_method' ) == 'enqueue' ? 'wp_enqueue_scripts' : 'wp_loaded',
            function () { ShortCode::linkAssets(); }
        );
    }

    /**
     * Link assets.
     */
    public static function linkAssets()
    {
        /** @var \WP_Locale $wp_locale */
        global $wp_locale;

        $link_style  = get_option( 'bookly_gen_link_assets_method' ) == 'enqueue' ? 'wp_enqueue_style'  : 'wp_register_style';
        $link_script = get_option( 'bookly_gen_link_assets_method' ) == 'enqueue' ? 'wp_enqueue_script' : 'wp_register_script';
        $version     = Lib\Plugin::getVersion();
        $resources   = plugins_url( 'frontend\resources', Lib\Plugin::getBasename() );

        // Assets for [bookly-form].
        if ( get_option( 'bookly_cst_phone_default_country' ) != 'disabled' ) {
            call_user_func( $link_style, 'bookly-intlTelInput', $resources . '/css/intlTelInput.css', array(), $version );
        }
        call_user_func( $link_style, 'bookly-ladda-min',    $resources . '/css/ladda.min.css',       array(), $version );
        call_user_func( $link_style, 'bookly-picker',       $resources . '/css/picker.classic.css',  array(), $version );
        call_user_func( $link_style, 'bookly-picker-date',  $resources . '/css/picker.classic.date.css', array(), $version );
        call_user_func( $link_style, 'bookly-main',         $resources . '/css/bookly-main.css',     get_option( 'bookly_cst_phone_default_country' ) != 'disabled' ? array( 'bookly-intlTelInput', 'bookly-picker-date' ) : array( 'bookly-picker-date' ), $version );
        if ( is_rtl() ) {
            call_user_func( $link_style, 'bookly-rtl',      $resources . '/css/bookly-rtl.css',      array(), $version );
        }
        call_user_func( $link_script, 'bookly-spin',        $resources . '/js/spin.min.js',          array(), $version );
        call_user_func( $link_script, 'bookly-ladda',       $resources . '/js/ladda.min.js',         array( 'bookly-spin' ), $version );
        call_user_func( $link_script, 'bookly-hammer',      $resources . '/js/hammer.min.js',        array( 'jquery' ), $version );
        call_user_func( $link_script, 'bookly-jq-hammer',   $resources . '/js/jquery.hammer.min.js', array( 'jquery' ), $version );
        call_user_func( $link_script, 'bookly-picker',      $resources . '/js/picker.js',            array( 'jquery' ), $version );
        call_user_func( $link_script, 'bookly-picker-date', $resources . '/js/picker.date.js',       array( 'bookly-picker' ), $version );
        if ( get_option( 'bookly_cst_phone_default_country' ) != 'disabled' ) {
            call_user_func( $link_script, 'bookly-intlTelInput', $resources . '/js/intlTelInput.min.js', array( 'jquery' ), $version );
        }
        if ( Lib\Config::showFacebookLoginButton() && ! get_current_user_id() ) {
            call_user_func( $link_script, 'bookly-facebook-sdk', sprintf( 'https://connect.facebook.net/%s/sdk.js', Lib\Config::getLocale() ), array(), $version );
        }
        call_user_func( $link_script, 'bookly', $resources . '/js/bookly.js', array( 'bookly-ladda', 'bookly-hammer', 'bookly-picker-date' ), $version );

        // Assets for [bookly-appointments-list].
        call_user_func( $link_style,  'bookly-customer-profile', plugins_url( 'frontend/modules/customer_profile/resources/css/customer_profile.css', Lib\Plugin::getBasename() ), array(), $version );
        call_user_func( $link_script, 'bookly-customer-profile', plugins_url( 'frontend/modules/customer_profile/resources/js/customer_profile.js', Lib\Plugin::getBasename() ), array( 'jquery' ), $version );

        Proxy\Shared::enqueueBookingAssets();

        wp_localize_script( 'bookly', 'BooklyL10n', array(
            'csrf_token' => Lib\Utils\Common::getCsrfToken(),
            'today'      => __( 'Today', 'bookly' ),
            'months'     => array_values( $wp_locale->month ),
            'days'       => array_values( $wp_locale->weekday ),
            'daysShort'  => array_values( $wp_locale->weekday_abbrev ),
            'nextMonth'  => __( 'Next month', 'bookly' ),
            'prevMonth'  => __( 'Previous month', 'bookly' ),
            'show_more'  => __( 'Show more', 'bookly' ),
        ) );
    }

    /**
     * Render Bookly shortcode.
     *
     * @param $attributes
     * @return string
     * @throws
     */
    public static function generate( $attributes )
    {
        global $sitepress;

        // Disable caching.
        Lib\Utils\Common::noCache();

        $assets = '';

        if ( get_option( 'bookly_gen_link_assets_method' ) == 'print' ) {
            $print_assets = ! wp_script_is( 'bookly', 'done' );
            if ( $print_assets ) {
                ob_start();

                // The styles and scripts are registered in Frontend.php
                wp_print_styles( 'bookly-intlTelInput' );
                wp_print_styles( 'bookly-ladda-min' );
                wp_print_styles( 'bookly-picker' );
                wp_print_styles( 'bookly-picker-date' );
                wp_print_styles( 'bookly-main' );

                wp_print_scripts( 'bookly-spin' );
                wp_print_scripts( 'bookly-ladda' );
                wp_print_scripts( 'bookly-picker' );
                wp_print_scripts( 'bookly-picker-date' );
                wp_print_scripts( 'bookly-hammer' );
                wp_print_scripts( 'bookly-jq-hammer' );
                wp_print_scripts( 'bookly-intlTelInput' );

                Proxy\Shared::printBookingAssets();

                wp_print_scripts( 'bookly' );

                $assets = ob_get_clean();
            }
        } else {
            $print_assets = true; // to print CSS in template.
        }

        // Generate unique form id.
        $form_id = uniqid();

        // Find bookings with any of payment statuses ( PayPal, 2Checkout, PayU Latam ).
        $status = array( 'booking' => 'new' );
        foreach ( Lib\Session::getAllFormsData() as $saved_form_id => $data ) {
            if ( isset ( $data['payment'] ) ) {
                if ( ! isset ( $data['payment']['processed'] ) ) {
                    switch ( $data['payment']['status'] ) {
                        case 'success':
                        case 'processing':
                            $form_id = $saved_form_id;
                            $status = array( 'booking' => 'finished' );
                            break;
                        case 'cancelled':
                        case 'error':
                            $form_id = $saved_form_id;
                            end( $data['cart'] );
                            $status = array( 'booking' => 'cancelled', 'cart_key' => key( $data['cart'] ) );
                            break;
                    }
                    // Mark this form as processed for cases when there are more than 1 booking form on the page.
                    $data['payment']['processed'] = true;
                    Lib\Session::setFormVar( $saved_form_id, 'payment', $data['payment'] );
                }
            } elseif ( $data['last_touched'] + 30 * MINUTE_IN_SECONDS < time() ) {
                // Destroy forms older than 30 min.
                Lib\Session::destroyFormData( $saved_form_id );
            }
        }

        // Handle shortcode attributes.
        $hide_date_and_time = (bool) @$attributes['hide_date_and_time'];
        $fields_to_hide = isset ( $attributes['hide'] ) ? explode( ',', $attributes['hide'] ) : array();
        $staff_member_id = (int) ( @$_GET['staff_id'] ?: @$attributes['staff_member_id'] );

        $attrs = array(
            'location_id'              => (int) ( @$_GET['loc_id'] ?: @$attributes['location_id'] ),
            'category_id'              => (int) ( @$_GET['cat_id'] ?: @$attributes['category_id'] ),
            'service_id'               => (int) ( @$_GET['service_id'] ?: @$attributes['service_id'] ),
            'staff_member_id'          => $staff_member_id,
            'hide_categories'          => in_array( 'categories', $fields_to_hide ) ? true : (bool) @$attributes['hide_categories'],
            'hide_services'            => in_array( 'services', $fields_to_hide ) ? true : (bool) @$attributes['hide_services'],
            'hide_staff_members'       => ( in_array( 'staff_members', $fields_to_hide ) ? true : (bool) @$attributes['hide_staff_members'] )
                                          && ( get_option( 'bookly_app_required_employee' ) ? $staff_member_id : true ),
            'hide_date'                => $hide_date_and_time ? true : in_array( 'date', $fields_to_hide ),
            'hide_week_days'           => $hide_date_and_time ? true : in_array( 'week_days', $fields_to_hide ),
            'hide_time_range'          => $hide_date_and_time ? true : in_array( 'time_range', $fields_to_hide ),
            'show_number_of_persons'   => (bool) @$attributes['show_number_of_persons'],
            'show_service_duration'    => (bool) get_option( 'bookly_app_service_name_with_duration' ),
            // Add-ons.
            'hide_service_duration'    => true,
            'hide_locations'           => true,
            'hide_quantity'            => true,
            'location_custom_settings' => (bool) Lib\Proxy\Locations::getAllowServicesPerLocation(),
        );
        // Set service step attributes for Add-ons.
        if ( Lib\Config::customDurationEnabled() ) {
            $attrs['hide_service_duration'] = in_array( 'service_duration', $fields_to_hide );
        }
        if ( Lib\Config::locationsEnabled() ) {
            $attrs['hide_locations'] = in_array( 'locations', $fields_to_hide );
        }
        if ( Lib\Config::multiplyAppointmentsEnabled() ) {
            $attrs['hide_quantity']  = in_array( 'quantity',  $fields_to_hide );
        }
        if ( Lib\Config::ratingsEnabled() ) {
            $attrs['show_ratings'] = (int) get_option( 'bookly_ratings_app_show_on_frontend' );
        }

        $service_part1 = (
            ! $attrs['show_number_of_persons'] &&
            $attrs['hide_categories'] &&
            $attrs['hide_services'] &&
            $attrs['service_id'] &&
            $attrs['hide_staff_members'] &&
            $attrs['hide_locations'] &&
            $attrs['hide_service_duration'] &&
            $attrs['hide_quantity']
        );

        $service_part2 = (
            $attrs['hide_date'] &&
            $attrs['hide_week_days'] &&
            $attrs['hide_time_range']
        );
        if ( $service_part1 && $service_part2 ) {
            // Store attributes in session for later use in Time step.
            Lib\Session::setFormVar( $form_id, 'attrs', $attrs );
            Lib\Session::setFormVar( $form_id, 'last_touched', time() );
        }
        $skip_steps = array(
            'service_part1' => (int) $service_part1,
            'service_part2' => (int) $service_part2,
            'extras' => (int) ( ! Lib\Config::serviceExtrasEnabled() ||
                $service_part1 && ! Lib\Proxy\ServiceExtras::findByServiceId( $attrs['service_id'] ) ),
            'repeat' => (int) ( ! Lib\Config::recurringAppointmentsEnabled() ),
        );
        // Prepare URL for AJAX requests.
        $ajax_url = admin_url( 'admin-ajax.php' );
        // Support WPML.
        if ( $sitepress instanceof \SitePress ) {
            $ajax_url .= ( strpos( $ajax_url, '?' ) ? '&' : '?' ) . 'lang=' . $sitepress->get_current_language();
        }
        $woocommerce_enabled = (int) Lib\Config::wooCommerceEnabled();
        $options = array(
            'time_slots_wide' => Lib\Config::showWideTimeSlots(),
            'intlTelInput' => array( 'enabled' => 0 ),
            'woocommerce'  => array( 'enabled' => $woocommerce_enabled, 'cart_url' => $woocommerce_enabled ? wc_get_cart_url() : '' ),
            'cart'         => array( 'enabled' => $woocommerce_enabled ? 0 : (int) Lib\Config::showStepCart() ),
            'facebook'     => array( 'enabled' => Lib\Config::showFacebookLoginButton() && ! get_current_user_id(), 'appId' => Lib\Config::getFacebookAppId() ),
            'google_map'   => array(
                'enabled'   => (int) Lib\Config::googleMapsAddressEnabled(),
                'api_key'   => get_option( 'bookly_google_api_key' ),
            ),
        );
        if ( get_option( 'bookly_cst_phone_default_country' ) != 'disabled' ) {
            $options['intlTelInput']['enabled'] = 1;
            $options['intlTelInput']['utils']   = is_rtl() ? '' : plugins_url( 'intlTelInput.utils.js', Lib\Plugin::getDirectory() . '/frontend/resources/js/intlTelInput.utils.js' );
            $options['intlTelInput']['country'] = get_option( 'bookly_cst_phone_default_country' );
        }
        $required = array(
            'staff' => (int) get_option( 'bookly_app_required_employee' )
        );
        if ( Lib\Config::locationsEnabled() ) {
            $required['location'] = (int) get_option( 'bookly_app_required_location' ) || (int) Lib\Proxy\Locations::getAllowServicesPerLocation();
        }

        // Custom CSS.
        $custom_css = get_option( 'bookly_app_custom_styles' );

        $errors = array(
            Errors::SESSION_ERROR               => __( 'Session error.', 'bookly' ),
            Errors::FORM_ID_ERROR               => __( 'Form ID error.', 'bookly' ),
            Errors::CART_ITEM_NOT_AVAILABLE     => Lib\Utils\Common::getTranslatedOption( Lib\Config::showStepCart() ? 'bookly_l10n_step_cart_slot_not_available' : 'bookly_l10n_step_time_slot_not_available' ),
            Errors::PAY_LOCALLY_NOT_AVAILABLE   => __( 'Pay locally is not available.', 'bookly' ),
            Errors::INVALID_GATEWAY             => __( 'Invalid gateway.', 'bookly' ),
            Errors::PAYMENT_ERROR               => __( 'Error.', 'bookly' ),
            Errors::INCORRECT_USERNAME_PASSWORD => __( 'Incorrect username or password.' ),
        );
        $errors = Proxy\Shared::prepareBookingErrorCodes( $errors );

        return $assets . self::renderTemplate(
            'short_code',
            compact( 'attrs', 'options', 'required', 'print_assets', 'form_id', 'ajax_url', 'status', 'skip_steps', 'custom_css', 'errors' ),
            false
        );
    }
}