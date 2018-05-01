<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib\Base;

/**
 * Class Locations
 * Invoke local methods from Locations add-on.
 *
 * @package Bookly\Lib\Proxy
 *
 * @method static void addBooklyMenuItem() Add 'Locations' to Bookly menu
 * @see \BooklyLocations\Lib\ProxyProviders\Local::addBooklyMenuItem()
 *
 * @method static \Booklylocations\Lib\Entities\Location|false findById( int $location_id ) Return Location entity.
 * @see \BooklyLocations\Lib\ProxyProviders\Local::findById()
 *
 * @method static \Booklylocations\Lib\Entities\Location[] findByStaffId( int $staff_id ) Return locations associated with given staff.
 * @see \BooklyLocations\Lib\ProxyProviders\Local::findByStaffId()
 *
 * @method static bool getAllowServicesPerLocation() Get allow-services-per-location option
 * @see \BooklyLocations\Lib\ProxyProviders\Local::getAllowServicesPerLocation()
 *
 * @method static int prepareStaffLocationId( int $location_id, int $staff_id ) Prepare StaffService Location Id
 * @see \BooklyLocations\Lib\ProxyProviders\Local::prepareStaffLocationId()
 *
 * @method static \Bookly\Lib\Query prepareStaffServiceQuery( \Bookly\Lib\Query $query ) Prepare StaffService query for Finder
 * @see \BooklyLocations\Lib\ProxyProviders\Local::prepareStaffServiceQuery()
 *
 * @method static void renderAppearance() Render Locations in Appearance
 * @see \BooklyLocations\Lib\ProxyProviders\Local::renderAppearance()
 *
 * @method static void renderStaffServices( int $staff_id, int $location_id ) Render Locations in Appearance
 * @see \BooklyLocations\Lib\ProxyProviders\Local::renderStaffServices()
 */
abstract class Locations extends Base\ProxyInvoker
{

}