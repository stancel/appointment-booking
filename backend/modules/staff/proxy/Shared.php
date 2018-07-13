<?php
namespace Bookly\Backend\Modules\Staff\Proxy;

use Bookly\Lib;

/**
 * Class Shared
 * @package Bookly\Backend\Modules\Staff\Proxy
 *
 * @method static void enqueueAssetsForStaffProfile() Enqueue assets for page Staff.
 * @method static void renderStaffForm( Lib\Entities\Staff $staff ) Render Staff form tab details.
 * @method static void renderStaffService( int $staff_id, Lib\Entities\Service $service, array $services_data, array $attributes = array() ) Render controls for staff on Services tab.
 * @method static void renderStaffServiceLabels() Render column header for controls on Services tab.
 * @method static void renderStaffServiceTail( int $staff_id, Lib\Entities\Service $service, int $location_id, $attributes = array() ) Render controls for Staff on tab services.
 * @method static void renderStaffTab( Lib\Entities\Staff $staff ) Render staff tab.
 * @method static void updateStaff( array $_post ) Update staff settings in add-ons.
 * @method static void updateStaffServices( array $_post ) Update staff services settings in add-ons.
 */
abstract class Shared extends Lib\Base\Proxy
{

}