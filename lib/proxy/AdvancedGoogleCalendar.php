<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib;

/**
 * Class Taxes
 * Invoke local methods from Advanced Google Calendar add-on.
 *
 * @package Bookly\Lib\Proxy
 *
 * @method static \BooklyAdvancedGoogleCalendar\Lib\Google\Calendar createApiCalendar( Lib\Google\Client $client ) Create new instance of Calendar.
 * @see \BooklyAdvancedGoogleCalendar\Lib\ProxyProviders\Local::createApiCalendar()
 *
 * @method static void renderSyncButton( array $staff_members ) Render Google Calendar sync button in Bookly calendar.
 * @see \BooklyAdvancedGoogleCalendar\Lib\ProxyProviders\Local::renderSyncButton()
 *
 * @method static void renderSettings() Render Google Calendar advanced settings in Bookly Settings.
 * @see \BooklyAdvancedGoogleCalendar\Lib\ProxyProviders\Local::renderSettings()
 *
 * @method static array preSaveSettings( array $alert, array $params ) Pre-save settings.
 * @see \BooklyAdvancedGoogleCalendar\Lib\ProxyProviders\Local::preSaveSettings()
 */
abstract class AdvancedGoogleCalendar extends Lib\Base\ProxyInvoker
{

}