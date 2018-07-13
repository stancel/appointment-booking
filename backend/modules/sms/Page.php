<?php
namespace Bookly\Backend\Modules\Sms;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Backend\Modules\Sms
 */
class Page extends Lib\Base\Component
{
    /**
     * Render page.
     */
    public static function render()
    {
        global $wp_locale;

        self::enqueueStyles( array(
            'frontend' => array_merge(
                array( 'css/ladda.min.css', ),
                get_option( 'bookly_cst_phone_default_country' ) == 'disabled'
                    ? array()
                    : array( 'css/intlTelInput.css' )
            ),
            'backend'  => array(
                'bootstrap/css/bootstrap-theme.min.css',
                'css/daterangepicker.css',
            ),
        ) );

        self::enqueueScripts( array(
            'backend'  => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/datatables.min.js'  => array( 'jquery' ),
                'js/moment.min.js',
                'js/daterangepicker.js' => array( 'jquery' ),
                'js/help.js'  => array( 'jquery' ),
                'js/alert.js' => array( 'jquery' ),
            ),
            'frontend' => array_merge(
                array(
                    'js/spin.min.js'  => array( 'jquery' ),
                    'js/ladda.min.js' => array( 'jquery' ),
                ),
                get_option( 'bookly_cst_phone_default_country' ) == 'disabled'
                    ? array()
                    : array( 'js/intlTelInput.min.js' => array( 'jquery' ) )
            ),
            'module'   => array( 'js/sms.js' => array( 'jquery' ) ),
        ) );

        $alert  = array( 'success' => array(), 'error' => array() );
        $prices = array();
        $form   = new \Bookly\Backend\Modules\Notifications\Forms\Notifications( 'sms' );
        $sms    = new Lib\SMS();
        $cron_reminder = (array) get_option( 'bookly_cron_reminder_times' );

        if ( self::hasParameter( 'form-login' ) ) {
            $sms->login( self::parameter( 'username' ), self::parameter( 'password' ) );
        } elseif ( self::hasParameter( 'form-logout' ) ) {
            $sms->logout();

        } elseif ( self::hasParameter( 'form-registration' ) ) {
            if ( self::parameter( 'accept_tos', false ) ) {
                $sms->register(
                    self::parameter( 'username' ),
                    self::parameter( 'password' ),
                    self::parameter( 'password_repeat' )
                );
            } else {
                $alert['error'][] = __( 'Please accept terms and conditions.', 'bookly' );
            }
        }

        $is_logged_in = $sms->loadProfile();

        if ( ! $is_logged_in ) {
            if ( $response = $sms->getPriceList() ) {
                $prices = $response->list;
            }
            if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
                // Hide authentication errors on auto login.
                $sms->clearErrors();
            }
        } else {
            switch ( self::parameter( 'paypal_result' ) ) {
                case 'success':
                    $alert['success'][] = __( 'Your payment has been accepted for processing.', 'bookly' );
                    break;
                case 'cancel':
                    $alert['error'][] = __( 'Your payment has been interrupted.', 'bookly' );
                    break;
            }
            if ( self::hasParameter( 'form-notifications' ) ) {
                update_option( 'bookly_sms_administrator_phone', self::parameter( 'bookly_sms_administrator_phone' ) );
                update_option( 'bookly_ntf_processing_interval', (int) self::parameter( 'bookly_ntf_processing_interval' ) );

                $form->bind( self::postParameters() );
                $form->save();
                $alert['success'][] = __( 'Settings saved.', 'bookly' );

                foreach ( array( 'staff_agenda', 'client_follow_up', 'client_reminder', 'client_birthday_greeting' ) as $type ) {
                    $cron_reminder[ $type ] = self::parameter( $type . '_cron_hour' );
                }
                foreach ( array( 'client_reminder_1st', 'client_reminder_2nd', 'client_reminder_3rd', ) as $type ) {
                    $cron_reminder[ $type ] = self::parameter( $type . '_cron_before_hour' );
                }
                update_option( 'bookly_cron_reminder_times', $cron_reminder );
            }
            if ( self::hasParameter( 'tab' ) ) {
                switch ( self::parameter( 'auto-recharge' ) ) {
                    case 'approved':
                        $alert['success'][] = __( 'Auto-Recharge enabled.', 'bookly' );
                        break;
                    case 'declined':
                        $alert['error'][] = __( 'You declined the Auto-Recharge of your balance.', 'bookly' );
                        break;
                }
            }
        }
        $current_tab = self::hasParameter( 'tab' ) ? self::parameter( 'tab' ) : 'notifications';
        $alert['error'] = array_merge( $alert['error'], $sms->getErrors() );
        wp_localize_script( 'bookly-daterangepicker.js', 'BooklyL10n',
            array(
                'csrf_token'    => Lib\Utils\Common::getCsrfToken(),
                'alert'         => $alert,
                'apply'         => __( 'Apply', 'bookly' ),
                'are_you_sure'  => __( 'Are you sure?', 'bookly' ),
                'cancel'        => __( 'Cancel', 'bookly' ),
                'country'       => get_option( 'bookly_cst_phone_default_country' ),
                'current_tab'   => $current_tab,
                'custom_range'  => __( 'Custom Range', 'bookly' ),
                'from'          => __( 'From', 'bookly' ),
                'last_30'       => __( 'Last 30 Days', 'bookly' ),
                'last_7'        => __( 'Last 7 Days', 'bookly' ),
                'last_month'    => __( 'Last Month', 'bookly' ),
                'mjsDateFormat' => Lib\Utils\DateTime::convertFormat( 'date', Lib\Utils\DateTime::FORMAT_MOMENT_JS ),
                'startOfWeek'   => (int) get_option( 'start_of_week' ),
                'this_month'    => __( 'This Month', 'bookly' ),
                'to'            => __( 'To', 'bookly' ),
                'today'         => __( 'Today', 'bookly' ),
                'yesterday'     => __( 'Yesterday', 'bookly' ),
                'input_old_password' => __( 'Please enter old password.',  'bookly' ),
                'passwords_no_same'  => __( 'Passwords must be the same.', 'bookly' ),
                'intlTelInput'  => array(
                    'country' => get_option( 'bookly_cst_phone_default_country' ),
                    'utils'   => is_rtl() ? '' : plugins_url( 'intlTelInput.utils.js', Lib\Plugin::getDirectory() . '/frontend/resources/js/intlTelInput.utils.js' ),
                    'enabled' => get_option( 'bookly_cst_phone_default_country' ) != 'disabled',
                ),
                'calendar'      => array(
                    'longDays'    => array_values( $wp_locale->weekday ),
                    'longMonths'  => array_values( $wp_locale->month ),
                    'shortDays'   => array_values( $wp_locale->weekday_abbrev ),
                    'shortMonths' => array_values( $wp_locale->month_abbrev ),
                ),
                'sender_id'     => array(
                    'sent'        => __( 'Sender ID request is sent.', 'bookly' ),
                    'set_default' => __( 'Sender ID is reset to default.', 'bookly' ),
                ),
                'zeroRecords'   => __( 'No records for selected period.', 'bookly' ),
                'zeroRecords2'  => __( 'No records.', 'bookly' ),
                'processing'    => __( 'Processing...', 'bookly' ),
            )
        );
        $cron_uri = plugins_url( 'lib/utils/send_notifications_cron.php', Lib\Plugin::getMainFile() );
        $statuses = Lib\Entities\CustomerAppointment::getStatuses();
        foreach ( range( 1, 23 ) as $hours ) {
            $bookly_ntf_processing_interval_values[] = array( $hours, Lib\Utils\DateTime::secondsToInterval( $hours * HOUR_IN_SECONDS ) );
        }
        self::renderTemplate( 'index', compact( 'form', 'sms', 'is_logged_in', 'prices', 'cron_uri', 'cron_reminder', 'statuses', 'bookly_ntf_processing_interval_values' ) );
    }
}