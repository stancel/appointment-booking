<?php
namespace Bookly\Backend\Modules\Analytics;

use Bookly\Lib;

/**
 * Class Page
 * @package Bookly\Backend\Modules\Analytics
 */
class Page extends Lib\Base\Component
{
    /**
     * Display page.
     */
    public static function render()
    {
        /** @var \WP_Locale $wp_locale */
        global $wp_locale;

        self::enqueueStyles( array(
            'frontend' => array( 'css/ladda.min.css', ),
            'backend'  => array(
                'css/select2.min.css',
                'bootstrap/css/bootstrap-theme.min.css',
                'css/daterangepicker.css',
            ),
        ) );

        self::enqueueScripts( array(
            'backend'  => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/datatables.min.js'   => array( 'jquery' ),
                'js/moment.min.js',
                'js/daterangepicker.js'  => array( 'jquery' ),
                'js/select2.full.min.js' => array( 'jquery' ),
            ),
            'frontend' => array(
                'js/spin.min.js'  => array( 'jquery' ),
                'js/ladda.min.js' => array( 'jquery' ),
            ),
            'module'   => array( 'js/analytics.js' => array( 'bookly-datatables.min.js' ), ),
        ) );

        $services = Lib\Entities\Service::query()
            ->select( 'id, title' )
            ->where( 'type', Lib\Entities\Service::TYPE_SIMPLE )
            ->indexBy( 'id' )
            ->fetchArray();
        array_unshift( $services, array( 'id' => 0, 'title' => __( 'Custom', 'bookly' ) ) );
        $staff_members = Lib\Entities\Staff::query()
            ->select( 'id, full_name AS title' )
            ->indexBy( 'id' )
            ->fetchArray();

        wp_localize_script( 'bookly-analytics.js', 'BooklyL10n', array(
            'csrfToken'    => Lib\Utils\Common::getCsrfToken(),
            'tomorrow'     => __( 'Tomorrow', 'bookly' ),
            'today'        => __( 'Today', 'bookly' ),
            'yesterday'    => __( 'Yesterday', 'bookly' ),
            'last7'        => __( 'Last 7 Days', 'bookly' ),
            'last30'       => __( 'Last 30 Days', 'bookly' ),
            'thisMonth'    => __( 'This Month', 'bookly' ),
            'nextMonth'    => __( 'Next Month', 'bookly' ),
            'customRange'  => __( 'Custom Range', 'bookly' ),
            'apply'        => __( 'Apply', 'bookly' ),
            'cancel'       => __( 'Cancel', 'bookly' ),
            'to'           => __( 'To', 'bookly' ),
            'from'         => __( 'From', 'bookly' ),
            'calendar'     => array(
                'longMonths'  => array_values( $wp_locale->month ),
                'shortMonths' => array_values( $wp_locale->month_abbrev ),
                'longDays'    => array_values( $wp_locale->weekday ),
                'shortDays'   => array_values( $wp_locale->weekday_abbrev ),
            ),
            'mjsDateFormat' => Lib\Utils\DateTime::convertFormat( 'date', Lib\Utils\DateTime::FORMAT_MOMENT_JS ),
            'startOfWeek'   => (int) get_option( 'start_of_week' ),
            'zeroRecords'   => __( 'No appointments for selected period.', 'bookly' ),
            'processing'    => __( 'Processing...', 'bookly' ),
            'services' => array(
                'allSelected'     => __( 'All services', 'bookly' ),
                'nothingSelected' => __( 'No service selected', 'bookly' ),
                'collection'      => $services,
            ),
            'staff' => array(
                'allSelected'     => __( 'All staff', 'bookly' ),
                'nothingSelected' => __( 'No staff selected', 'bookly' ),
                'collection'      => $staff_members,
            ),
        ) );

        self::renderTemplate( 'index', compact( 'staff_members', 'services' ) );
    }
}