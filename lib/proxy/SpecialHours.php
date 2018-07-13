<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib;

/**
 * Class SpecialHours
 * @package Bookly\Lib\Proxy
 *
 * @method static float adjustPrice( float $price, int $staff_id, int $service_id, int $location_id, $start_time, int $units ) Adjust price for given staff, service and time.
 */
abstract class SpecialHours extends Lib\Base\Proxy
{
}