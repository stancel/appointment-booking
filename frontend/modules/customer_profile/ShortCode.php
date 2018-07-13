<?php
namespace Bookly\Frontend\Modules\CustomerProfile;

use Bookly\Lib;
use Bookly\Lib\Entities\Stat;

/**
 * Class ShortCode
 * @package Bookly\Frontend\Modules\CustomerProfile
 */
class ShortCode extends Lib\Base\Ajax
{
    /**
     * Render shortcode.
     *
     * @param array $attributes
     * @return string
     */
    public static function generate( $attributes )
    {
        global $sitepress;

        // Disable caching.
        Lib\Utils\Common::noCache();

        $assets = '';

        if ( get_option( 'bookly_gen_link_assets_method' ) == 'print' ) {
            if ( ! wp_script_is( 'bookly-customer-profile', 'done' ) ) {
                ob_start();

                // The styles and scripts are registered in Frontend.php
                wp_print_styles( 'bookly-customer-profile' );
                wp_print_scripts( 'bookly-customer-profile' );

                $assets = ob_get_clean();
            }
        }

        $customer = new Lib\Entities\Customer();
        $customer->loadBy( array( 'wp_user_id' => get_current_user_id() ) );
        if ( $customer->isLoaded() ) {
            $appointments = self::_translateAppointments( $customer->getUpcomingAppointments() );
            $expired      = $customer->getPastAppointments( 1, 1 );
            $more   = ! empty ( $expired['appointments'] );
        } else {
            $appointments = array();
            $more   = false;
        }
        $allow_cancel = current_time( 'timestamp' );
        $minimum_time_prior_cancel = (int) get_option( 'bookly_gen_min_time_prior_cancel', 0 );
        if ( $minimum_time_prior_cancel > 0 ) {
            $allow_cancel += $minimum_time_prior_cancel * HOUR_IN_SECONDS;
        }

        // Prepare URL for AJAX requests.
        $ajax_url = admin_url( 'admin-ajax.php' );

        // Support WPML.
        if ( $sitepress instanceof \SitePress ) {
            $ajax_url = add_query_arg( array( 'lang' => $sitepress->get_current_language() ) , $ajax_url );
        }

        $titles = array();
        if ( @$attributes['show_column_titles'] ) {
            $titles = array(
                'category' => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_category' ),
                'service'  => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_service' ),
                'staff'    => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_employee' ),
                'date'     => __( 'Date',   'bookly' ),
                'time'     => __( 'Time',   'bookly' ),
                'price'    => __( 'Price',  'bookly' ),
                'cancel'   => __( 'Cancel', 'bookly' ),
                'status'   => __( 'Status', 'bookly' ),
            );
            if ( Lib\Config::customFieldsEnabled() ) {
                foreach ( (array) Lib\Proxy\CustomFields::getTranslated() as $field ) {
                    if ( ! in_array( $field->type, array( 'captcha', 'text-content', 'file' ) ) ) {
                        $titles[ $field->id ] = $field->label;
                    }
                }
            }
        }

        $url_cancel = add_query_arg( array( 'action' => 'bookly_cancel_appointment', 'csrf_token' => Lib\Utils\Common::getCsrfToken() ) , $ajax_url );
        if ( is_user_logged_in() ) {
            Stat::record( 'view_customer_profile', 1 );
        }

        return $assets . self::renderTemplate( 'short_code', compact( 'ajax_url', 'appointments', 'attributes', 'url_cancel', 'titles', 'more', 'allow_cancel' ), false );
    }

    /**
     * WPML translation
     *
     * @param array $appointments
     * @return array
     */
    protected static function _translateAppointments( array $appointments )
    {
        $postfix_any = sprintf( ' (%s)', Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_option_employee' ) );
        foreach ( $appointments as &$appointment ) {
            $category = new Lib\Entities\Category( array( 'id' => $appointment['category_id'], 'name' => $appointment['category'] ) );
            $service  = new Lib\Entities\Service( array( 'id' => $appointment['service_id'],  'title' => $appointment['service'] ) );
            $staff    = new Lib\Entities\Staff( array( 'id' => $appointment['staff_id'],  'full_name' => $appointment['staff'] ) );
            $appointment['category'] = $category->getTranslatedName();
            $appointment['service']  = $service->getTranslatedTitle();
            $appointment['staff']    = $staff->getTranslatedName() . ( $appointment['staff_any'] ? $postfix_any : '' );
            // Prepare extras.
            $appointment['extras']   = (array) Lib\Proxy\ServiceExtras::getInfo( json_decode( $appointment['extras'], true ), true );
        }

        return $appointments;
    }
}