<?php
namespace Bookly\Backend\Modules\Settings;

use Bookly\Lib;

/**
 * Class Page
 * @package Bookly\Backend\Modules\Settings
 */
class Page extends Lib\Base\Ajax
{
    /**
     * Render page.
     */
    public static function render()
    {
        /** @var \WP_Locale $wp_locale */
        global $wp_locale;

        wp_enqueue_media();
        self::enqueueStyles( array(
            'frontend' => array( 'css/ladda.min.css' ),
            'backend'  => array( 'bootstrap/css/bootstrap-theme.min.css', )
        ) );

        self::enqueueScripts( array(
            'backend'  => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/jCal.js'  => array( 'jquery' ),
                'js/alert.js' => array( 'jquery' ),
            ),
            'module'   => array( 'js/settings.js' => array( 'jquery', 'bookly-intlTelInput.min.js', 'jquery-ui-sortable' ) ),
            'frontend' => array(
                'js/intlTelInput.min.js' => array( 'jquery' ),
                'js/spin.min.js'  => array( 'jquery' ),
                'js/ladda.min.js' => array( 'jquery' ),
            )
        ) );

        $current_tab = self::hasParameter( 'tab' ) ? self::parameter( 'tab' ) : 'general';
        $alert = array( 'success' => array(), 'error' => array() );

        // Save the settings.
        if ( ! empty ( $_POST ) ) {
            if ( self::csrfTokenValid() ) {
                switch ( self::parameter( 'tab' ) ) {
                    case 'calendar':  // Calendar form.
                        update_option( 'bookly_cal_one_participant',   self::parameter( 'bookly_cal_one_participant' ) );
                        update_option( 'bookly_cal_many_participants', self::parameter( 'bookly_cal_many_participants' ) );
                        $alert['success'][] = __( 'Settings saved.', 'bookly' );
                        break;
                    case 'payments':  // Payments form.
                        $form = new Forms\Payments();
                        break;
                    case 'business_hours':  // Business hours form.
                        $form = new Forms\BusinessHours();
                        break;
                    case 'purchase_code':  // Purchase Code form.
                        $errors = apply_filters( 'bookly_save_purchase_codes', array(), self::parameter( 'purchase_code' ), null );
                        if ( empty ( $errors ) ) {
                            $alert['success'][] = __( 'Settings saved.', 'bookly' );
                        } else {
                            $alert['error'] = array_merge( $alert['error'], $errors );
                        }
                        break;
                    case 'general':  // General form.
                        $bookly_gen_time_slot_length = self::parameter( 'bookly_gen_time_slot_length' );
                        if ( in_array( $bookly_gen_time_slot_length, array( 5, 10, 12, 15, 20, 30, 45, 60, 90, 120, 180, 240, 360 ) ) ) {
                            update_option( 'bookly_gen_time_slot_length', $bookly_gen_time_slot_length );
                        }
                        update_option( 'bookly_gen_service_duration_as_slot_length', (int) self::parameter( 'bookly_gen_service_duration_as_slot_length' ) );
                        update_option( 'bookly_gen_allow_staff_edit_profile', (int) self::parameter( 'bookly_gen_allow_staff_edit_profile' ) );
                        update_option( 'bookly_gen_default_appointment_status', self::parameter( 'bookly_gen_default_appointment_status' ) );
                        update_option( 'bookly_gen_link_assets_method', self::parameter( 'bookly_gen_link_assets_method' ) );
                        update_option( 'bookly_gen_max_days_for_booking', (int) self::parameter( 'bookly_gen_max_days_for_booking' ) );
                        update_option( 'bookly_gen_min_time_prior_booking', self::parameter( 'bookly_gen_min_time_prior_booking' ) );
                        update_option( 'bookly_gen_min_time_prior_cancel', self::parameter( 'bookly_gen_min_time_prior_cancel' ) );
                        update_option( 'bookly_gen_use_client_time_zone', (int) self::parameter( 'bookly_gen_use_client_time_zone' ) );
                        if ( Lib\Plugin::getPurchaseCode() ) {
                            update_option( 'bookly_gen_collect_stats', self::parameter( 'bookly_gen_collect_stats' ) );
                        }
                        $alert['success'][] = __( 'Settings saved.', 'bookly' );
                        break;
                    case 'url': // URL settings form.
                        update_option( 'bookly_url_approve_page_url', self::parameter( 'bookly_url_approve_page_url' ) );
                        update_option( 'bookly_url_approve_denied_page_url', self::parameter( 'bookly_url_approve_denied_page_url' ) );
                        update_option( 'bookly_url_cancel_page_url', self::parameter( 'bookly_url_cancel_page_url' ) );
                        update_option( 'bookly_url_cancel_denied_page_url', self::parameter( 'bookly_url_cancel_denied_page_url' ) );
                        update_option( 'bookly_url_cancel_confirm_page_url', self::parameter( 'bookly_url_cancel_confirm_page_url' ) );
                        update_option( 'bookly_url_reject_denied_page_url', self::parameter( 'bookly_url_reject_denied_page_url' ) );
                        update_option( 'bookly_url_reject_page_url', self::parameter( 'bookly_url_reject_page_url' ) );
                        update_option( 'bookly_url_final_step_url', self::parameter( 'bookly_url_final_step_url' ) );
                        $alert['success'][] = __( 'Settings saved.', 'bookly' );
                        break;
                    case 'google_calendar':  // Google calendar form.
                        $alert = Proxy\AdvancedGoogleCalendar::preSaveSettings( $alert, self::postParameters() );
                        update_option( 'bookly_gc_client_id', self::parameter( 'bookly_gc_client_id' ) );
                        update_option( 'bookly_gc_client_secret', self::parameter( 'bookly_gc_client_secret' ) );
                        update_option( 'bookly_gc_sync_mode', self::parameter( 'bookly_gc_sync_mode' ) );
                        update_option( 'bookly_gc_limit_events', self::parameter( 'bookly_gc_limit_events' ) );
                        update_option( 'bookly_gc_event_title', self::parameter( 'bookly_gc_event_title' ) );
                        $alert['success'][] = __( 'Settings saved.', 'bookly' );
                        break;
                    case 'facebook':  // Facebook.
                        update_option( 'bookly_fb_app_id', self::parameter( 'bookly_fb_app_id' ) );
                        $alert['success'][] = __( 'Settings saved.', 'bookly' );
                        break;
                    case 'customers':  // Customers form.
                        update_option( 'bookly_cst_cancel_action',              self::parameter( 'bookly_cst_cancel_action' ) );
                        update_option( 'bookly_cst_combined_notifications',     self::parameter( 'bookly_cst_combined_notifications' ) );
                        update_option( 'bookly_cst_create_account',             self::parameter( 'bookly_cst_create_account' ) );
                        update_option( 'bookly_cst_required_address',           self::parameter( 'bookly_cst_required_address' ) );
                        update_option( 'bookly_cst_address_show_fields',        self::parameter( 'bookly_cst_address_show_fields', array() ) );
                        update_option( 'bookly_cst_default_country_code',       self::parameter( 'bookly_cst_default_country_code' ) );
                        update_option( 'bookly_cst_new_account_role',           self::parameter( 'bookly_cst_new_account_role' ) );
                        update_option( 'bookly_cst_phone_default_country',      self::parameter( 'bookly_cst_phone_default_country' ) );
                        update_option( 'bookly_cst_remember_in_cookie',         self::parameter( 'bookly_cst_remember_in_cookie' ) );
                        update_option( 'bookly_cst_allow_duplicates',           self::parameter( 'bookly_cst_allow_duplicates' ) );
                        update_option( 'bookly_cst_show_update_details_dialog', self::parameter( 'bookly_cst_show_update_details_dialog' ) );
                        // Update email required option if creating wordpress account for customers
                        $bookly_cst_required_details = get_option( 'bookly_cst_required_details', array() );
                        if ( self::parameter( 'bookly_cst_create_account' ) && ! in_array( 'email', $bookly_cst_required_details ) ) {
                            $bookly_cst_required_details[] = 'email';
                            update_option( 'bookly_cst_required_details', $bookly_cst_required_details );
                        }
                        $alert['success'][] = __( 'Settings saved.', 'bookly' );
                        break;
                    case 'woo_commerce':  // WooCommerce form.
                        foreach ( array( 'bookly_l10n_wc_cart_info_name', 'bookly_l10n_wc_cart_info_value' ) as $option_name ) {
                            update_option( $option_name, self::parameter( $option_name ) );
                            do_action( 'wpml_register_single_string', 'bookly', $option_name, self::parameter( $option_name ) );
                        }
                        update_option( 'bookly_wc_enabled', self::parameter( 'bookly_wc_enabled' ) );
                        update_option( 'bookly_wc_product', self::parameter( 'bookly_wc_product' ) );
                        $alert['success'][] = __( 'Settings saved.', 'bookly' );
                        break;
                    case 'company':  // Company form.
                        update_option( 'bookly_co_address', self::parameter( 'bookly_co_address' ) );
                        update_option( 'bookly_co_logo_attachment_id', self::parameter( 'bookly_co_logo_attachment_id' ) );
                        update_option( 'bookly_co_name',    self::parameter( 'bookly_co_name' ) );
                        update_option( 'bookly_co_phone',   self::parameter( 'bookly_co_phone' ) );
                        update_option( 'bookly_co_website', self::parameter( 'bookly_co_website' ) );
                        $alert['success'][] = __( 'Settings saved.', 'bookly' );
                        break;
                }

                // Let Add-ons save their settings.
                $alert = Proxy\Shared::saveSettings( $alert, self::parameter( 'tab' ), self::postParameters() );

                if ( in_array( self::parameter( 'tab' ), array( 'payments', 'business_hours' ) ) ) {
                    $form->bind( self::postParameters(), $_FILES );
                    $form->save();

                    $alert['success'][] = __( 'Settings saved.', 'bookly' );
                }
            }
        }

        $candidates = self::_getCandidatesBooklyProduct();

        // Check if WooCommerce cart exists.
        if ( get_option( 'bookly_wc_enabled' ) && class_exists( 'WooCommerce', false ) ) {
            $post = get_post( wc_get_page_id( 'cart' ) );
            if ( $post === null || $post->post_status != 'publish' ) {
                $alert['error'][] = sprintf(
                    __( 'WooCommerce cart is not set up. Follow the <a href="%s">link</a> to correct this problem.', 'bookly' ),
                    Lib\Utils\Common::escAdminUrl( 'wc-status', array( 'tab' => 'tools' ) )
                );
            }
        }

        wp_localize_script( 'bookly-jCal.js', 'BooklyL10n',  array(
            'alert'              => $alert,
            'current_tab'        => $current_tab,
            'csrf_token'         => Lib\Utils\Common::getCsrfToken(),
            'default_country'    => get_option( 'bookly_cst_phone_default_country' ),
            'holidays'           => self::_getHolidays(),
            'loading_img'        => plugins_url( 'appointment-booking/backend/resources/images/loading.gif' ),
            'start_of_week'      => get_option( 'start_of_week' ),
            'days'               => array_values( $wp_locale->weekday_abbrev ),
            'months'             => array_values( $wp_locale->month ),
            'close'              => __( 'Close', 'bookly' ),
            'repeat'             => __( 'Repeat every year', 'bookly' ),
            'we_are_not_working' => __( 'We are not working on this day', 'bookly' ),
            'confirm_detach'     => sprintf( __( "Are you sure you want to dissociate this purchase code from %s?\n\nThis will also remove the entered purchase code from this site.", 'bookly' ), get_site_url() ),
            'sample_price'       => number_format_i18n( 10, 3 ),
        ) );
        $values = array(
            'bookly_gc_limit_events' => array( array( '0', __( 'Disabled', 'bookly' ) ), array( 25, 25 ), array( 50, 50 ), array( 100, 100 ), array( 250, 250 ), array( 500, 500 ), array( 1000, 1000 ), array( 2500, 2500 ) ),
            'bookly_gen_min_time_prior_booking' => array( array( '0', __( 'Disabled', 'bookly' ) ) ),
            'bookly_gen_min_time_prior_cancel'  => array( array( '0', __( 'Disabled', 'bookly' ) ) ),
        );
        $wp_roles = new \WP_Roles();
        foreach ( $wp_roles->get_names() as $role => $name ) {
            $values['bookly_cst_new_account_role'][] = array( $role, $name );
        }
        foreach ( array( 5, 10, 12, 15, 20, 30, 45, 60, 90, 120, 180, 240, 360 ) as $duration ) {
            $values['bookly_gen_time_slot_length'][] = array( $duration, Lib\Utils\DateTime::secondsToInterval( $duration * MINUTE_IN_SECONDS ) );
        }
        foreach ( array_merge( array( 0.5 ), range( 1, 12 ), range( 24, 144, 24 ), range( 168, 672, 168 ) ) as $hour ) {
            $values['bookly_gen_min_time_prior_booking'][] = array( $hour, Lib\Utils\DateTime::secondsToInterval( $hour * HOUR_IN_SECONDS ) );
        }
        foreach ( array_merge( array( 1 ), range( 2, 12, 2 ), range( 24, 168, 24 ) ) as $hour ) {
            $values['bookly_gen_min_time_prior_cancel'][] = array( $hour, Lib\Utils\DateTime::secondsToInterval( $hour * HOUR_IN_SECONDS ) );
        }
        $states = Lib\Config::getPluginVerificationStates();
        $grace_remaining_days = $states['grace_remaining_days'];

        // Payments tab
        $payments     = array();
        $payment_data = array(
            'local'  => self::renderTemplate( '_payment_local', array(), false ),
            'paypal' => self::renderTemplate( '_payment_paypal', array(), false ),
        );
        $payment_data = Proxy\Shared::preparePaymentGatewaySettings( $payment_data );
        $order        = explode( ',', get_option( 'bookly_pmt_order' ) );
        if ( $order ) {
            foreach ( $order as $payment_system ) {
                if ( array_key_exists( $payment_system, $payment_data ) ) {
                    $payments[] = $payment_data[ $payment_system ];
                }
            }
        }
        foreach ( $payment_data as $slug => $data ) {
            if ( ! $order || ! in_array( $slug, $order ) ) {
                $payments[] = $data;
            }
        }

        self::renderTemplate( 'index', compact( 'candidates', 'values', 'grace_remaining_days', 'payments' ) );
    }

    /**
     * Get holidays.
     *
     * @return array
     */
    protected static function _getHolidays()
    {
        $collection = Lib\Entities\Holiday::query()->where( 'staff_id', null )->fetchArray();
        $holidays = array();
        if ( count( $collection ) ) {
            foreach ( $collection as $holiday ) {
                $holidays[ $holiday['id'] ] = array(
                    'm' => (int) date( 'm', strtotime( $holiday['date'] ) ),
                    'd' => (int) date( 'd', strtotime( $holiday['date'] ) ),
                );
                // If not repeated holiday, add the year
                if ( ! $holiday['repeat_event'] ) {
                    $holidays[ $holiday['id'] ]['y'] = (int) date( 'Y', strtotime( $holiday['date'] ) );
                }
            }
        }

        return $holidays;
    }

    /**
     * @return array
     */
    protected static function _getCandidatesBooklyProduct()
    {
        /** @global \wpdb $wpdb */
        global $wpdb;

        $goods    = array( array( 'id' => 0, 'name' => __( 'Select product', 'bookly' ) ) );
        $query    = 'SELECT ID, post_title FROM ' . $wpdb->posts . ' WHERE post_type = \'product\' AND post_status = \'publish\' ORDER BY post_title';
        $products = $wpdb->get_results( $query );

        foreach ( $products as $product ) {
            $goods[] = array( 'id' => $product->ID, 'name' => $product->post_title );
        }

        return $goods;
    }
}