<?php
namespace Bookly\Backend\Modules\Staff;

use Bookly\Lib;

/**
 * Class Page
 * @package Bookly\Backend\Modules\Staff
 */
class Page extends Lib\Base\Component
{
    /**
     * Render page.
     */
    public static function render()
    {
        wp_enqueue_media();
        self::enqueueStyles( array(
            'frontend' => array_merge(
                array( 'css/ladda.min.css', ),
                get_option( 'bookly_cst_phone_default_country' ) == 'disabled'
                    ? array()
                    : array( 'css/intlTelInput.css' )
            ),
            'backend'  => array( 'bootstrap/css/bootstrap-theme.min.css', 'css/jquery-ui-theme/jquery-ui.min.css' )
        ) );

        self::enqueueScripts( array(
            'backend'  => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/jCal.js'  => array( 'jquery' ),
                'js/alert.js' => array( 'jquery' ),
                'js/range_tools.js' => array( 'jquery' ),
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
            'module' => array(
                'js/staff-details.js'  => array( 'bookly-alert.js', ),
                'js/staff-services.js' => array( 'bookly-staff-details.js' ),
                'js/staff-schedule.js' => array( 'bookly-staff-services.js' ),
                'js/staff-days-off.js' => array( 'bookly-staff-schedule.js' ),
                'js/staff.js'          => array( 'jquery-ui-sortable', 'jquery-ui-datepicker', 'bookly-range_tools.js', 'bookly-staff-days-off.js' ),
            ),
        ) );

        wp_localize_script( 'bookly-staff.js', 'BooklyL10n', array(
            'are_you_sure'      => __( 'Are you sure?', 'bookly' ),
            'saved'             => __( 'Settings saved.', 'bookly' ),
            'capacity_error'    => __( 'Min capacity should not be greater than max capacity.', 'bookly' ),
            'selector'          => array( 'all_selected' => __( 'All locations', 'bookly' ), 'nothing_selected' => __( 'No locations selected', 'bookly' ), ),
            'intlTelInput'      => array(
                'enabled' => get_option( 'bookly_cst_phone_default_country' ) != 'disabled',
                'utils'   => is_rtl() ? '' : plugins_url( 'intlTelInput.utils.js', Lib\Plugin::getDirectory() . '/frontend/resources/js/intlTelInput.utils.js' ),
                'country' => get_option( 'bookly_cst_phone_default_country' ),
            ),
            'csrf_token'        => Lib\Utils\Common::getCsrfToken(),
            'locations_custom'  => (int) Lib\Proxy\Locations::getAllowServicesPerLocation(),
        ) );

        // Allow add-ons to enqueue their assets.
        Proxy\Shared::enqueueAssetsForStaffProfile();

        $staff_members = Lib\Utils\Common::isCurrentUserAdmin()
            ? Lib\Entities\Staff::query()->sortBy( 'position' )->fetchArray()
            : Lib\Entities\Staff::query()->where( 'wp_user_id', get_current_user_id() )->fetchArray();

        if ( self::hasParameter( 'staff_id' ) ) {
            $active_staff_id = self::parameter( 'staff_id' );
        } else {
            $active_staff_id = empty ( $staff_members ) ? 0 : $staff_members[0]['id'];
        }

        // Check if this request is the request after google auth, set the token-data to the staff.
        if ( self::hasParameter( 'code' ) ) {
            $google = new Lib\Google\Client();
            $token  = $google->exchangeCodeForAccessToken( self::parameter( 'code' ) );

            if ( $token ) {
                $staff_id = (int) base64_decode( strtr( self::parameter( 'state' ), '-_,', '+/=' ) );
                $staff = new Lib\Entities\Staff();
                $staff->load( $staff_id );
                $staff
                    ->setGoogleData( json_encode( array(
                        'token'    => $token,
                        'calendar' => array( 'id' => null, 'sync_token' => null ),
                        'channel'  => array( 'id' => null, 'resource_id' => null, 'expiration' => null ),
                    ) ) )
                    ->save()
                ;

                exit ( sprintf( '<script>location.href="%s";</script>', admin_url( 'admin.php?page=' . self::pageSlug() . '&staff_id=' . $staff_id ) ) );
            } else {
                Lib\Session::set( 'staff_google_auth_error', json_encode( $google->getErrors() ) );
            }
        }

        if ( self::hasParameter( 'google_logout' ) ) {
            $active_staff_id = self::parameter( 'google_logout' );
            $staff = new Lib\Entities\Staff();
            if ( $staff->load( $active_staff_id ) ) {
                $google = new Lib\Google\Client();
                if ( $google->auth( $staff ) ) {
                    if ( Lib\Config::advancedGoogleCalendarActive() ) {
                        $google->calendar()->stopWatching( false );
                    }
                    $google->revokeToken();
                }
                $staff
                    ->setGoogleData( null )
                    ->save()
                ;
            }
        }
        $form = new Forms\StaffMember();
        $users_for_staff = $form->getUsersForStaff();

        self::renderTemplate( 'index', compact( 'staff_members', 'users_for_staff', 'active_staff_id' ) );
    }
}