<?php
namespace Bookly\Backend\Modules\Appearance\Proxy;

use Bookly\Lib;

/**
 * Class Shared
 * @package Bookly\Backend\Modules\Appearance\Proxy
 *
 * @method static array prepareOptions( array $options_to_save, array $options ) Alter array of options to be saved in Bookly Appearance.
 * @method static void  renderPaymentGatewaySelector() Render gateway selector.
 * @method static void  renderRepeatStep( string $progress_tracker ) Render Repeat step.
 * @method static void  renderServiceStepSettings( int $col ) Render checkbox settings.
 * @method static void  renderTimeStepSettings() Render checkbox settings.
 * @method static bool  showCreditCard( bool $required ) In case there are payment systems that request credit card information in the Details step, it will return true.
 */
abstract class Shared extends Lib\Base\Proxy
{

}