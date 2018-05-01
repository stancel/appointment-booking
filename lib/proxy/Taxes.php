<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib\Base;
use Bookly\Lib\CartItem;

/**
 * Class Taxes
 * Invoke local methods from Taxes add-on.
 *
 * @package Bookly\Lib\Proxy
 *
 * @method static void renderServiceTaxes( array $service ) Render taxes
 * @see \BooklyTaxes\Lib\ProxyProviders\Local::renderServiceTaxes()
 *
 * @method static void addBooklyMenuItem() Add 'Taxes' to Bookly menu
 * @see \BooklyTaxes\Lib\ProxyProviders\Local::addBooklyMenuItem()
 *
 * @method static float getAmountOfTax( CartItem $cart_item )
 * @see \BooklyTaxes\Lib\ProxyProviders\Local::getAmountOfTax()
 *
 * @method static float calculateTax( float $amount, float $rate )
 * @see \BooklyTaxes\Lib\ProxyProviders\Local::calculateTax()
 *
 * @method static array accumulationRateAmounts( array $amounts, CartItem $cart_item, bool $allow_coupon )
 * @see \BooklyTaxes\Lib\ProxyProviders\Local::accumulationRateAmounts()
 *
 * @method static array getServiceRates()
 * @see \BooklyTaxes\Lib\ProxyProviders\Local::getServiceRates()
 *
 * @method static void renderAppearance() Render taxes in Appearance
 * @see \BooklyTaxes\Lib\ProxyProviders\Local::renderAppearance()
 *
 * @method static void renderPaymentTaxHelpMessage() Render payment tax message
 * @see \BooklyTaxes\Lib\ProxyProviders\Local::renderPaymentTaxHelpMessage()
 *
 */
abstract class Taxes extends Base\ProxyInvoker
{

}