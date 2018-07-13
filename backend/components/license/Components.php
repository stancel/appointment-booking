<?php
namespace Bookly\Backend\Components\License;

use Bookly\Lib;

/**
 * Class Components
 * @package Bookly\Backend\Components\License
 */
class Components extends Lib\Base\Component
{
    /**
     * Render license required form.
     */
    public static function renderLicenseRequired()
    {
        if ( Lib\Config::booklyExpired() || get_option( 'bookly_grace_hide_admin_notice_time' ) < time() ) {
            $states = Lib\Config::getPluginVerificationStates();
            $role   = Lib\Utils\Common::isCurrentUserAdmin() ? 'admin' : 'staff';
            if ( Lib\Config::booklyExpired() ) {
                self::_enqueueAssets();
                self::renderTemplate( 'board', array( 'board_body' => self::renderTemplate( $role . '_grace_ended', compact( 'states' ), false ) ) );
            } elseif ( $states['grace_remaining_days'] ) {
                // Some plugin in grace period
                self::_enqueueAssets();
                $days_text = array( '{days}' => sprintf( _n( '%d day', '%d days', $states['grace_remaining_days'], 'bookly' ), $states['grace_remaining_days'] ) );
                self::renderTemplate( 'board', array( 'board_body' => self::renderTemplate( $role . '_grace', compact( 'states', 'days_text' ), false ) ) );
            }
        }
    }

    /**
     * Render license notice.
     *
     * @param bool $bookly_page
     */
    public static function renderLicenseNotice( $bookly_page )
    {
        $states = Lib\Config::getPluginVerificationStates();
        if ( ! $bookly_page && Lib\Config::booklyExpired() ) {
            self::_enqueueAssets();
            $replace_data = array(
                '{url}'  => Lib\Utils\Common::escAdminUrl( \Bookly\Backend\Modules\Settings\Ajax::pageSlug(), array( 'tab' => 'purchase_code' ) ),
            );
            self::renderTemplate( 'notice_grace_ended', compact( 'replace_data' ) );
        } elseif ( $states['grace_remaining_days'] ) {
            if ( ! $bookly_page ) {
                self::_enqueueAssets();
            }
            $replace_data = array(
                '{url}'  => Lib\Utils\Common::escAdminUrl( \Bookly\Backend\Modules\Settings\Ajax::pageSlug(), array( 'tab' => 'purchase_code' ) ),
                '{days}' => sprintf( _n( '%d day', '%d days', $states['grace_remaining_days'], 'bookly' ), $states['grace_remaining_days'] ),
            );
            self::renderTemplate( 'notice_grace', compact( 'replace_data' ) );
        }
    }

    /**
     * Enqueue assets.
     */
    private static function _enqueueAssets()
    {
        self::enqueueStyles( array(
            'backend' => array( 'bootstrap/css/bootstrap-theme.min.css', ),
        ) );

        self::enqueueScripts( array(
            'module'  => array( 'js/license.js' => array( 'jquery' ), ),
            'backend' => array(
                'js/alert.js' => array( 'jquery' ),
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
            ),
        ) );

        wp_localize_script( 'bookly-license.js', 'LicenseL10n', array(
            'csrf_token' => Lib\Utils\Common::getCsrfToken()
        ) );
    }
}