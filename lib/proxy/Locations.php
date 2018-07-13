<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib;

/**
 * Class Locations
 * @package Bookly\Lib\Proxy
 *
 * @method static void addBooklyMenuItem() Add 'Locations' to Bookly menu.
 * @method static \Booklylocations\Lib\Entities\Location|false findById( int $location_id ) Find location by id
 * @method static \Booklylocations\Lib\Entities\Location[] findByStaffId( int $staff_id ) Find locations by staff id.
 * @method static bool getAllowServicesPerLocation() Get allow-services-per-location option.
 * @method static int prepareStaffLocationId( int $location_id, int $staff_id ) Prepare StaffService Location Id.
 * @method static Lib\Query prepareStaffServiceQuery( Lib\Query $query ) Prepare StaffService query for Finder.
 */
abstract class Locations extends Lib\Base\Proxy
{

}