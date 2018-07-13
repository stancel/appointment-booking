<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib;

/**
 * Class Shared
 * @package Bookly\Lib\Proxy
 *
 * @method static array  adjustMinAndMaxTimes( array $times ) Prepare time_from & time_to for UserBookingData.
 * @method static Lib\CartInfo applyPaymentSpecificPrice( Lib\CartInfo $cart_info, $gateway ) Correcting price for payment system.
 * @method static array  getOutdatedUnpaidPayments( array $payments ) Get list of outdated unpaid payments
 * @method static void   deleteCustomerAppointment( Lib\Entities\CustomerAppointment $ca ) Deleting customer appointment
 * @method static void   doDailyRoutine() Execute daily routine.
 * @method static array  handleRequestAction( string $bookly_action ) Handle requests with given action.
 * @method static array  prepareCaSeSt( array $result ) Prepare Categories Services Staff data
 * @method static Lib\Query prepareCaSeStQuery( Lib\Query $query ) Prepare CaSeSt query
 * @method static array  prepareCategoryServiceStaffLocation( array $location_data, array $row ) Prepare Category Service Staff Location data by row
 * @method static array  prepareCategoryService( array $result, array $row ) Prepare Category Service data by row
 * @method static void   prepareNotificationCodesForOrder( Lib\NotificationCodes $codes ) Prepare codes for replacing in notifications
 * @method static array  prepareNotificationNames( array $names ) Prepare notification names.
 * @method static array  prepareNotificationTypeIds( array $type_ids ) Prepare notification type IDs.
 * @method static array  preparePaymentDetails( array $details, Lib\DataHolders\Booking\Order $order, Lib\CartInfo $cart_info ) Add info about payment
 * @method static array  prepareReplaceCodes( array $codes, Lib\NotificationCodes $notification_codes, $format ) Replacing on booking steps
 * @method static Lib\NotificationCodes prepareTestNotificationCodes( Lib\NotificationCodes $codes ) Prepare codes for testing email templates
 * @method static bool   showPaymentSpecificPrices( bool $show ) Say if need show price for each payment system.
 */
abstract class Shared extends Lib\Base\Proxy
{
}
