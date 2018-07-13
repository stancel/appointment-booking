<?php
namespace Bookly\Backend\Components\Dialogs\Appointment\AttachPayment;

use Bookly\Lib;

/**
 * Class Dialog
 * @package Bookly\Backend\Components\Dialogs\Appointment\AttachPayment
 */
class Dialog extends Lib\Base\Component
{
    public static function render()
    {
        static::renderTemplate( 'attach_payment' );
    }
}