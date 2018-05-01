<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib;

/**
 * Class Cart
 * Invoke local methods from Cart add-on.
 *
 * @package Bookly\Lib\Proxy
 *
 * @method static string getStepHtml( Lib\UserBookingData $userData, string $progress_tracker, string $info_text ) Render cart step.
 * @see \BooklyCart\Lib\ProxyProviders\Local::getStepHtml()
 *
 * @method static void renderAppearance( string $progress_tracker ) Render cart step in appearance.
 * @see \BooklyCart\Lib\ProxyProviders\Local::renderAppearance()
 */
abstract class Cart extends Lib\Base\ProxyInvoker
{

}