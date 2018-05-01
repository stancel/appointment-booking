<?php
namespace Bookly\Backend\Modules\Appearance;

use Bookly\Lib;
use Bookly\Backend\Modules\Appearance\Lib\Helper;

/**
 * Class Controller
 * @package Bookly\Backend\Modules\Appearance
 */
class Controller extends Lib\Base\Controller
{
    const page_slug = 'bookly-appearance';

    /**
     *  Default Action
     */
    public function index()
    {
        /** @var \WP_Locale $wp_locale */
        global $wp_locale;

        $this->enqueueStyles( array(
            'frontend' => array_merge(
                ( get_option( 'bookly_cst_phone_default_country' ) == 'disabled'
                    ? array()
                    : array( 'css/intlTelInput.css' ) ),
                array(
                    'css/ladda.min.css',
                    'css/picker.classic.css',
                    'css/picker.classic.date.css',
                    'css/bookly-main.css',
                )
            ),
            'backend' => array( 'bootstrap/css/bootstrap-theme.min.css', ),
            'wp'      => array( 'wp-color-picker', ),
            'module'  => array( 'css/bootstrap-editable.css', )
        ) );

        $this->enqueueScripts( array(
            'backend' => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/alert.js' => array( 'jquery' ),
            ),
            'frontend' => array_merge(
                array(
                    'js/picker.js' => array( 'jquery' ),
                    'js/picker.date.js' => array( 'jquery' ),
                    'js/spin.min.js'    => array( 'jquery' ),
                    'js/ladda.min.js'   => array( 'jquery' ),
                ),
                get_option( 'bookly_cst_phone_default_country' ) == 'disabled'
                    ? array()
                    : array( 'js/intlTelInput.min.js' => array( 'jquery' ) )
            ),
            'wp'     => array( 'wp-color-picker' ),
            'module' => array(
                'js/bootstrap-editable.min.js'    => array( 'bookly-bootstrap.min.js' ),
                'js/bootstrap-editable.bookly.js' => array( 'bookly-bootstrap-editable.min.js' ),
                'js/appearance.js'                => array( 'bookly-bootstrap-editable.bookly.js' )
            )
        ) );

        wp_localize_script( 'bookly-picker.date.js', 'BooklyL10n', array(
            'csrf_token'    => Lib\Utils\Common::getCsrfToken(),
            'today'         => __( 'Today', 'bookly' ),
            'months'        => array_values( $wp_locale->month ),
            'days'          => array_values( $wp_locale->weekday_abbrev ),
            'nextMonth'     => __( 'Next month', 'bookly' ),
            'prevMonth'     => __( 'Previous month', 'bookly' ),
            'date_format'   => Lib\Utils\DateTime::convertFormat( 'date', Lib\Utils\DateTime::FORMAT_PICKADATE ),
            'start_of_week' => (int) get_option( 'start_of_week' ),
            'saved'         => __( 'Settings saved.', 'bookly' ),
            'intlTelInput'  => array(
                'enabled' => get_option( 'bookly_cst_phone_default_country' ) != 'disabled',
                'utils'   => is_rtl() ? '' : plugins_url( 'intlTelInput.utils.js', Lib\Plugin::getDirectory() . '/frontend/resources/js/intlTelInput.utils.js' ),
                'country' => get_option( 'bookly_cst_phone_default_country' ),
            ),
            'facebook'      => array(
                'configured' => Lib\Config::getFacebookAppId() != '',
            ),
        ) );

        // Initialize steps (tabs).
        $steps = array(
            1 => get_option( 'bookly_l10n_step_service' ),
            3 => get_option( 'bookly_l10n_step_time' ),
            6 => get_option( 'bookly_l10n_step_details' ),
            7 => get_option( 'bookly_l10n_step_payment' ),
            8 => get_option( 'bookly_l10n_step_done' )
        );
        if ( Lib\Config::serviceExtrasEnabled() ) {
            $steps[2] = get_option( 'bookly_l10n_step_extras' );
        }
        if ( Lib\Config::recurringAppointmentsEnabled() ) {
            $steps[4] = get_option( 'bookly_l10n_step_repeat' );
        }
        if ( Lib\Config::cartEnabled() ) {
            $steps[5] = get_option( 'bookly_l10n_step_cart' );
        }
        ksort( $steps );

        // Time zone switcher.
        $current_offset = get_option('gmt_offset');
        $tz_string = get_option('timezone_string');
        if ( $tz_string == '' ) { // Create a UTC+- zone if no timezone string exists
            if ( $current_offset == 0 ) {
                $tz_string = 'UTC+0';
            }
            else if ( $current_offset < 0 ) {
                $tz_string = 'UTC' . $current_offset;
            }
            else {
                $tz_string = 'UTC+' . $current_offset;
            }
        }

        $custom_css = get_option( 'bookly_app_custom_styles' );

        // Shortcut to helper class.
        $editable = new Helper();

        // Render general layout.
        $this->render( 'index', compact( 'steps', 'tz_string', 'custom_css', 'editable' ) );
    }

    /**
     *  Update options
     */
    public function executeUpdateAppearanceOptions()
    {
        $options = $this->getParameter( 'options', array() );

        // Make sure that we save only allowed options.
        $options_to_save = array_intersect_key( $options, array_flip( array(
            // Info text.
            'bookly_l10n_info_complete_step',
            'bookly_l10n_info_complete_step_limit_error',
            'bookly_l10n_info_complete_step_processing',
            'bookly_l10n_info_details_step',
            'bookly_l10n_info_details_step_guest',
            'bookly_l10n_info_payment_step_single_app',
            'bookly_l10n_info_payment_step_several_apps',
            'bookly_l10n_info_service_step',
            'bookly_l10n_info_time_step',
            // Step, label and option texts.
            'bookly_l10n_button_apply',
            'bookly_l10n_button_back',
            'bookly_l10n_label_category',
            'bookly_l10n_label_ccard_code',
            'bookly_l10n_label_ccard_expire',
            'bookly_l10n_label_ccard_number',
            'bookly_l10n_label_email',
            'bookly_l10n_label_employee',
            'bookly_l10n_label_finish_by',
            'bookly_l10n_label_name',
            'bookly_l10n_label_first_name',
            'bookly_l10n_label_last_name',
            'bookly_l10n_label_notes',
            'bookly_l10n_info_address',
            'bookly_l10n_label_country',
            'bookly_l10n_label_state',
            'bookly_l10n_label_postcode',
            'bookly_l10n_label_city',
            'bookly_l10n_label_street',
            'bookly_l10n_label_additional_address',
            'bookly_l10n_label_birthday_day',
            'bookly_l10n_label_birthday_month',
            'bookly_l10n_label_birthday_year',
            'bookly_l10n_label_number_of_persons',
            'bookly_l10n_label_pay_ccard',
            'bookly_l10n_label_pay_locally',
            'bookly_l10n_label_pay_paypal',
            'bookly_l10n_label_phone',
            'bookly_l10n_label_select_date',
            'bookly_l10n_label_service',
            'bookly_l10n_label_start_from',
            'bookly_l10n_option_category',
            'bookly_l10n_option_employee',
            'bookly_l10n_option_service',
            'bookly_l10n_option_day',
            'bookly_l10n_option_month',
            'bookly_l10n_option_year',
            'bookly_l10n_step_service',
            'bookly_l10n_step_service_mobile_button_next',
            'bookly_l10n_step_service_button_next',
            'bookly_l10n_step_time',
            'bookly_l10n_step_time_slot_not_available',
            'bookly_l10n_step_details',
            'bookly_l10n_step_details_button_next',
            'bookly_l10n_step_details_button_login',
            'bookly_l10n_step_payment',
            'bookly_l10n_step_payment_button_next',
            'bookly_l10n_step_done',
            // Validator errors.
            'bookly_l10n_required_email',
            'bookly_l10n_required_employee',
            'bookly_l10n_required_name',
            'bookly_l10n_required_first_name',
            'bookly_l10n_required_last_name',
            'bookly_l10n_required_phone',
            'bookly_l10n_required_service',
            'bookly_l10n_required_country',
            'bookly_l10n_required_state',
            'bookly_l10n_required_postcode',
            'bookly_l10n_required_city',
            'bookly_l10n_required_street',
            'bookly_l10n_required_additional_address',
            'bookly_l10n_invalid_day',
            'bookly_l10n_required_day',
            'bookly_l10n_required_month',
            'bookly_l10n_required_year',
            // Color.
            'bookly_app_color',
            // Checkboxes.
            'bookly_app_required_employee',
            'bookly_app_service_name_with_duration',
            'bookly_app_show_blocked_timeslots',
            'bookly_app_show_calendar',
            'bookly_app_show_day_one_column',
            'bookly_app_show_time_zone_switcher',
            'bookly_app_show_login_button',
            'bookly_app_show_facebook_login_button',
            'bookly_app_show_notes',
            'bookly_app_show_birthday',
            'bookly_app_show_address',
            'bookly_app_show_progress_tracker',
            'bookly_app_staff_name_with_price',
            'bookly_cst_required_details',
            'bookly_cst_first_last_name',
        ) ) );

        // Allow add-ons to add their options.
        $options_to_save = Lib\Proxy\Shared::prepareAppearanceOptions( $options_to_save, $options );

        // Save options.
        foreach ( $options_to_save as $option_name => $option_value ) {
            update_option( $option_name, $option_value );
            // Register string for translate in WPML.
            if ( strpos( $option_name, 'bookly_l10n_' ) === 0 ) {
                do_action( 'wpml_register_single_string', 'bookly', $option_name, $option_value );
            }
        }

        wp_send_json_success();
    }

    /**
     * Ajax request to dismiss appearance notice for current user.
     */
    public function executeDismissAppearanceNotice()
    {
        update_user_meta( get_current_user_id(), Lib\Plugin::getPrefix() . 'dismiss_appearance_notice', 1 );
    }

    /**
     * Process ajax request to save custom css
     */
    public function executeSaveCustomCss()
    {
        update_option( 'bookly_app_custom_styles', $this->getParameter( 'custom_css' ) );

        wp_send_json_success( array( 'message' => __( 'Your custom CSS was saved. Please refresh the page to see your changes.', 'bookly') ) );
    }
}