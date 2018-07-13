<?php
namespace Bookly\Backend\Components\Notices;

use Bookly\Lib;

/**
 * Class PurchaseReminder
 * @package Bookly\Backend\Components\Notices
 */
class PurchaseReminder extends Lib\Base\Component
{
    /**
     * Render purchase reminder.
     */
    public static function render()
    {
        if ( get_user_meta( get_current_user_id(), 'show_purchase_reminder' ) ) {
            self::renderTemplate( 'purchase_reminder' );
        }
    }
}