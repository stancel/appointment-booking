<?php
namespace Bookly\Backend\Components\License;

use Bookly\Lib;

/**
 * Class Ajax
 * @package Bookly\Backend\Components\License
 */
class Ajax extends Lib\Base\Ajax
{
    /**
     * Render form for verification purchase codes.
     */
    public static function verifyPurchaseCodeForm()
    {
        wp_send_json_success( array( 'html' => self::renderTemplate( 'verification', array(), false ) ) );
    }

    /**
     * Purchase code verification.
     */
    public static function verifyPurchaseCode()
    {
        $purchase_code = self::parameter( 'purchase_code' );
        /** @var Lib\Base\Plugin $plugin_class */
        $plugin_class  = self::parameter( 'plugin' ) . '\Lib\Plugin';
        $result = Lib\API::verifyPurchaseCode( $purchase_code, $plugin_class );
        $response = array( 'success' => $result['valid'] );
        if ( $result['valid'] ) {
            $plugin_class::updatePurchaseCode( $purchase_code );
        } else {
            $response['data']['message'] = $result['error'];
        }

        wp_send_json( $response );
    }

    /**
     * One hour no show message License Required.
     */
    public static function graceHideAdminNotice()
    {
        update_option( 'bookly_grace_hide_admin_notice_time', time() + HOUR_IN_SECONDS );
        wp_send_json_success();
    }

    /**
     * Render window with message license verification succeeded.
     */
    public static function verificationSucceeded()
    {
        wp_send_json_success( array( 'html' => self::renderTemplate( 'verification_succeeded', array(), false ) ) );
    }

    /**
     * Dismiss purchase reminder.
     */
    public static function dismissPurchaseReminder()
    {
        delete_user_meta( get_current_user_id(), 'show_purchase_reminder' );
    }
}