<?php
namespace Bookly\Backend\Modules\Settings;

use Bookly\Lib;

/**
 * Class Ajax
 * @package Bookly\Backend\Modules\Settings
 */
class Ajax extends Page
{
    /**
     * Ajax request for Holidays calendar
     */
    public static function settingsHoliday()
    {
        global $wpdb;

        $id      = self::parameter( 'id',  false );
        $day     = self::parameter( 'day', false );
        $holiday = self::parameter( 'holiday' ) == 'true';
        $repeat  = (int) ( self::parameter( 'repeat' ) == 'true' );

        // update or delete the event
        if ( $id ) {
            if ( $holiday ) {
                $wpdb->update( Lib\Entities\Holiday::getTableName(), array( 'repeat_event' => $repeat ), array( 'id' => $id ), array( '%d' ) );
                $wpdb->update( Lib\Entities\Holiday::getTableName(), array( 'repeat_event' => $repeat ), array( 'parent_id' => $id ), array( '%d' ) );
            } else {
                Lib\Entities\Holiday::query()->delete()->where( 'id', $id )->where( 'parent_id', $id, 'OR' )->execute();
            }
            // add the new event
        } elseif ( $holiday && $day ) {
            $holiday = new Lib\Entities\Holiday( );
            $holiday
                ->setDate( $day )
                ->setRepeatEvent( $repeat )
                ->save();
            foreach ( Lib\Entities\Staff::query()->fetchArray() as $employee ) {
                $staff_holiday = new Lib\Entities\Holiday();
                $staff_holiday
                    ->setDate( $day)
                    ->setRepeatEvent( $repeat )
                    ->setStaffId( $employee['id'] )
                    ->setParent( $holiday )
                    ->save();
            }
        }

        // and return refreshed events
        echo json_encode( self::_getHolidays() );
        exit;
    }

    /**
     * Detach purchase code.
     */
    public static function detachPurchaseCode()
    {
        $option_name = self::parameter( 'name' );

        /** @var Lib\Base\Plugin $plugin_class */
        foreach ( apply_filters( 'bookly_plugins', array() ) as $plugin_class ) {
            if ( $plugin_class::getPurchaseCodeOption() == $option_name ) {
                if ( Lib\API::detachPurchaseCode( $plugin_class ) ) {
                    $plugin_class::updatePurchaseCode( '' );
                    wp_send_json_success( array( 'alert' => array( 'success' => array( __( 'Settings saved.', 'bookly' ) ) ) ) );
                }
            }
        }

        wp_send_json_error( array( 'alert' => array( 'error' => array( __( 'Error dissociating purchase code.', 'bookly' ) ) ) ) );
    }
}