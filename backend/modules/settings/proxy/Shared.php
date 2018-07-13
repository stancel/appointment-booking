<?php
namespace Bookly\Backend\Modules\Settings\Proxy;

use Bookly\Lib;

/**
 * Class Shared
 * @package Bookly\Backend\Modules\Settings\Proxy
 *
 * @method static array prepareCalendarAppointmentCodes( array $codes, string $participants ) Prepare codes for appointment description displayed in calendar.
 * @method static array preparePaymentGatewaySettings( array $payment_data ) Prepare gateway add-on payment settings.
 * @method static array preparePaymentOptions( array $options ) Alter payment option names before saving in Bookly Settings.
 * @method static array prepareWooCommerceCodes( array $codes ) Alter array of codes to be displayed in WooCommerce (Order,Cart,Checkout etc.).
 * @method static void  renderCartSettings() Render Cart settings on Settings page
 * @method static void  renderSettingsForm() Render add-on settings form.
 * @method static void  renderSettingsMenu() Render tab in settings page.
 * @method static void  renderUrlSettings() Render URL settings on Settings page.
 * @method static array saveSettings( array $alert, string $tab, $_post ) Save add-on settings.
 */
abstract class Shared extends Lib\Base\Proxy
{

}