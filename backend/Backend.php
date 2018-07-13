<?php
namespace Bookly\Backend;

use Bookly\Frontend;
use Bookly\Lib;

/**
 * Class Backend
 * @package Bookly\Backend
 */
class Backend
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        // Backend components.
        Components\Appearance\Proxy\Shared::init();
        Components\Dialogs\Appointment\Delete\Ajax::init();
        Components\Dialogs\Appointment\Edit\Ajax::init();
        Components\Dialogs\Customer\EditAjax::init();
        Components\License\Ajax::init();
        Components\Notices\CollectStatsAjax::init();
        Components\Notices\NpsAjax::init();
        Components\Notices\SubscribeAjax::init();
        Components\Support\ButtonsAjax::init();
        Components\TinyMce\Tools::init();

        // Backend pages.
        Modules\Analytics\Ajax::init();
        Modules\Appearance\Ajax::init();
        Modules\Appointments\Ajax::init();
        Modules\Calendar\Ajax::init();
        Modules\Customers\Ajax::init();
        Modules\Debug\Ajax::init();
        Modules\Messages\Ajax::init();
        Modules\Notifications\Ajax::init();
        Modules\Payments\Ajax::init();
        Modules\Services\Ajax::init();
        Modules\Settings\Ajax::init();
        Modules\Shop\Ajax::init();
        Modules\Sms\Ajax::init();
        Modules\Staff\Ajax::init();

        // Frontend controllers that work via admin-ajax.php.
        Frontend\Modules\Booking\Ajax::init();
        Frontend\Modules\CustomerProfile\Ajax::init();
        Frontend\Modules\WooCommerce\Ajax::init();

        add_action( 'admin_menu', array( $this, 'addAdminMenu' ) );
        add_action( 'wp_loaded',  array( $this, 'init' ) );
    }

    /**
     * Init.
     */
    public function init()
    {
        if ( ! session_id() ) {
            @session_start();
        }
    }

    /**
     * Admin menu.
     */
    public function addAdminMenu()
    {
        /** @var \WP_User $current_user */
        global $current_user, $submenu;

        if ( $current_user->has_cap( 'administrator' ) || Lib\Entities\Staff::query()->where( 'wp_user_id', $current_user->ID )->count() ) {
            $dynamic_position = '80.0000001' . mt_rand( 1, 1000 ); // position always is under `Settings`
            $badge_number = Modules\Messages\Page::getMessagesCount() + Modules\Shop\Page::getNotSeenCount();

            if ( $badge_number ) {
                add_menu_page( 'Bookly', sprintf( 'Bookly <span class="update-plugins count-%d"><span class="update-count">%d</span></span>', $badge_number, $badge_number ), 'read', 'bookly-menu', '',
                    plugins_url( 'resources/images/menu.png', __FILE__ ), $dynamic_position );
            } else {
                add_menu_page( 'Bookly', 'Bookly', 'read', 'bookly-menu', '',
                    plugins_url( 'resources/images/menu.png', __FILE__ ), $dynamic_position );
            }
            if ( Lib\Config::booklyExpired() ) {
                add_submenu_page( 'bookly-menu', __( 'License verification', 'bookly' ), __( 'License verification', 'bookly' ), 'read',
                    Modules\Settings\Ajax::pageSlug(), function () { Modules\License\Page::render(); } );
            } else {
                // Translated submenu pages.
                $calendar       = __( 'Calendar',            'bookly' );
                $appointments   = __( 'Appointments',        'bookly' );
                $staff_members  = __( 'Staff Members',       'bookly' );
                $services       = __( 'Services',            'bookly' );
                $sms            = __( 'SMS Notifications',   'bookly' );
                $notifications  = __( 'Email Notifications', 'bookly' );
                $customers      = __( 'Customers',           'bookly' );
                $payments       = __( 'Payments',            'bookly' );
                $appearance     = __( 'Appearance',          'bookly' );
                $settings       = __( 'Settings',            'bookly' );
                $analytics      = __( 'Analytics',           'bookly' );

                add_submenu_page( 'bookly-menu', $calendar, $calendar, 'read',
                    Modules\Calendar\Page::pageSlug(), function () { Modules\Calendar\Page::render(); } );
                add_submenu_page( 'bookly-menu', $appointments, $appointments, 'manage_options',
                    Modules\Appointments\Page::pageSlug(), function () { Modules\Appointments\Page::render(); } );
                Lib\Proxy\Locations::addBooklyMenuItem();
                Lib\Proxy\Packages::addBooklyMenuItem();
                if ( $current_user->has_cap( 'administrator' ) ) {
                    add_submenu_page( 'bookly-menu', $staff_members, $staff_members, 'manage_options',
                        Modules\Staff\Page::pageSlug(), function () { Modules\Staff\Page::render(); } );
                } else {
                    if ( get_option( 'bookly_gen_allow_staff_edit_profile' ) == 1 ) {
                        add_submenu_page( 'bookly-menu', __( 'Profile', 'bookly' ), __( 'Profile', 'bookly' ), 'read',
                            Modules\Staff\Page::pageSlug(), function () { Modules\Staff\Page::render(); } );
                    }
                }
                add_submenu_page( 'bookly-menu', $services, $services, 'manage_options',
                    Modules\Services\Page::pageSlug(), function () { Modules\Services\Page::render(); } );
                Lib\Proxy\Taxes::addBooklyMenuItem();
                add_submenu_page( 'bookly-menu', $customers, $customers, 'manage_options',
                    Modules\Customers\Page::pageSlug(), function () { Modules\Customers\Page::render(); } );
                Lib\Proxy\CustomerInformation::addBooklyMenuItem();
                Lib\Proxy\CustomerGroups::addBooklyMenuItem();
                add_submenu_page( 'bookly-menu', $notifications, $notifications, 'manage_options',
                    Modules\Notifications\Page::pageSlug(), function () { Modules\Notifications\Page::render(); } );
                add_submenu_page( 'bookly-menu', $sms, $sms, 'manage_options',
                    Modules\Sms\Page::pageSlug(), function () { Modules\Sms\Page::render(); } );
                add_submenu_page( 'bookly-menu', $payments, $payments, 'manage_options',
                    Modules\Payments\Page::pageSlug(), function () { Modules\Payments\Page::render(); } );
                add_submenu_page( 'bookly-menu', $appearance, $appearance, 'manage_options',
                    Modules\Appearance\Page::pageSlug(), function () { Modules\Appearance\Page::render(); } );
                Lib\Proxy\Coupons::addBooklyMenuItem();
                Lib\Proxy\CustomFields::addBooklyMenuItem();
                add_submenu_page( 'bookly-menu', $settings, $settings, 'manage_options',
                    Modules\Settings\Page::pageSlug(), function () { Modules\Settings\Page::render(); } );
                Modules\Messages\Page::addBooklyMenuItem();
                Modules\Shop\Page::addBooklyMenuItem();
                add_submenu_page( 'bookly-menu', $analytics, $analytics, 'manage_options',
                    Modules\Analytics\Page::pageSlug(), function () { Modules\Analytics\Page::render(); } );

                if ( isset ( $_GET['page'] ) && $_GET['page'] == 'bookly-debug' ) {
                    add_submenu_page( 'bookly-menu', 'Debug', 'Debug', 'manage_options',
                        Modules\Debug\Page::pageSlug(), function () { Modules\Debug\Page::render(); } );
                }
            }

            unset ( $submenu['bookly-menu'][0] );
        }
    }
}