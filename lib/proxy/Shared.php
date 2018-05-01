<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib;
use Bookly\Backend;

/**
 * Class Shared
 * Invoke shared methods.
 *
 * @package Bookly\Lib\Proxy
 *
 * @method static array  adjustMinAndMaxTimes( array $times ) Prepare time_from & time_to for UserBookingData.
 * @method static Lib\CartInfo applyPaymentSpecificPrice( Lib\CartInfo $cart_info, $gateway ) Correcting price for payment system.
 * @method static array  buildNotificationCodesList( array $codes, string $notification_type, array $codes_data ) Build array of codes to be displayed in notification template.
 * @method static array  getOutdatedUnpaidPayments( array $payments ) Get list of outdated unpaid payments
 * @method static void   deleteCustomerAppointment( Lib\Entities\CustomerAppointment $ca ) Deleting customer appointment
 * @method static void   doDailyRoutine() Execute daily routine.
 * @method static void   enqueueAssetsForAppointmentForm() Enqueue assets for Appointment Form
 * @method static void   enqueueAssetsForServices() Enqueue assets for page Services
 * @method static void   enqueueAssetsForStaffProfile() Enqueue assets for page Staff
 * @method static void   enqueueBookingAssets() Enqueue assets for booking form
 * @method static bool   existSpecificPriceSettings( string $gateway ) Exist settings for specific price
 * @method static array  handleRequestAction( string $bookly_action ) Handle requests with given action.
 * @method static array  prepareAppearanceCodes( array $codes ) Alter array of codes to be displayed in Bookly Appearance.
 * @method static array  prepareAppearanceOptions( array $options_to_save, array $options ) Alter array of options to be saved in Bookly Appearance.
 * @method static array  prepareCalendarAppointmentCodes( array $codes, string $participants ) Prepare codes for appointment description displayed in calendar.
 * @method static array  prepareBookingErrorCodes( array $errors ) Prepare array for errors on booking steps
 * @method static array  prepareCalendarAppointmentCodesData( array $codes, array $appointment_data, string $participants ) Prepare codes data for appointment description displayed in calendar.
 * @method static array  prepareCartItemInfoText( array $data, Lib\CartItem $cart_item ) Prepare array for replacing in Cart items
 * @method static array  prepareCaSeSt( array $result ) Prepare Categories Services Staff data
 * @method static \Bookly\Lib\Query prepareCaSeStQuery( \Bookly\Lib\Query $query ) Prepare CaSeSt query
 * @method static array  prepareChainItemInfoText( array $data, Lib\ChainItem $chain_item ) Prepare array for replacing in Chain items
 * @method static array  prepareInfoTextCodes( array $info_text_codes, array $data ) Prepare array for replacing on booking steps
 * @method static array  prepareNotificationCodes( array $codes, string $type ) Alter codes for displaying in notification templates.
 * @method static void   prepareNotificationCodesForOrder( Lib\NotificationCodes $codes ) Prepare codes for replacing in notifications
 * @method static array  prepareNotificationNames( array $names ) Prepare notification names.
 * @method static array  prepareNotificationTypes( array $types ) Prepare notification types.
 * @method static array  prepareNotificationTypeIds( array $type_ids ) Prepare notification type IDs.
 * @method static array  preparePaymentDetails( array $details, Lib\DataHolders\Booking\Order $order, Lib\CartInfo $cart_info ) Add info about payment
 * @method static array  preparePaymentGatewaySettings( array $payment_data ) Prepare gateway add-on payment settings
 * @method static void   preparePaymentGatewaySelector( array $payment_data, $form_id, array $payment, Lib\CartInfo $cart_info, bool $show_price ) Render gateway selector on step Payment
 * @method static array  preparePaymentOptions( array $options ) Alter payment option names before saving in Bookly Settings.
 * @method static array  prepareReplaceCodes( array $codes, Lib\NotificationCodes $notification_codes, $format ) Replacing on booking steps
 * @method static Lib\NotificationCodes prepareTestNotificationCodes( Lib\NotificationCodes $codes ) Prepare codes for testing email templates
 * @method static array  prepareUpdateServiceResponse( array $response, Lib\Entities\Service $service, array $_post ) Prepare response for updated service.
 * @method static array  prepareServiceColors( array $colors, int $service_id, int $service_type ) Prepare colors for service.
 * @method static array  prepareWooCommerceShortCodes( array $codes ) Alter array of codes to be displayed in WooCommerce (Order,Cart,Checkout etc.).
 * @method static array  prepareUpdateService( array $data ) Prepare update service settings in add-ons
 * @method static string prepareInfoMessage( string $default, Lib\UserBookingData $userData, int $step ) Prepare info message.
 * @method static void   printBookingAssets() Print assets for booking form.
 * @method static array  addPaymentSpecificPrices( array $pay, Lib\CartInfo $cart_info ) Add price for each payment system.
 * @method static void   renderAfterServiceList( array $service_collection ) Render content after services forms
 * @method static void   renderAppearancePaymentGatewaySelector() Render gateway selector
 * @method static void   renderAppearanceStepServiceSettings() Render checkbox settings
 * @method static void   renderAppointmentDialogCustomerList() Render content in AppointmentForm for customers
 * @method static void   renderAppointmentDialogFooter() Render buttons in appointments dialog footer.
 * @method static void   renderCartItemInfo( Lib\UserBookingData $userData, $cart_key, $positions, $desktop ) Render in cart extra info for CartItem
 * @method static void   renderCartSettings() Render Cart settings on Settings page
 * @method static void   renderChainItemHead() Render head for chain in step service
 * @method static void   renderChainItemTail() Render tail for chain in step service
 * @method static void   renderComponentAppointments() Render content in appointments
 * @method static void   renderComponentCalendar() Render content in calendar page
 * @method static void   renderCustomerDialogCustomField( \stdClass $custom_field ) Render custom filed in customer dialog
 * @method static void   renderEmailNotifications( Backend\Modules\Notifications\Forms\Notifications $form ) Render email notification(s)
 * @method static void   renderMediaButtons( string $version ) Add buttons to WordPress editor.
 * @method static void   renderPaymentGatewayForm( $form_id, $page_url ) Render gateway form on step Payment
 * @method static void   renderPopUpShortCodeBooklyForm() Render controls in popup for bookly-form (build shortcode)
 * @method static void   renderPopUpShortCodeBooklyFormHead() Render controls in header popup for bookly-form (build shortcode)
 * @method static void   renderServiceForm( array $service ) Render content in service form
 * @method static void   renderServiceFormHead( array $service ) Render top content in service form
 * @method static void   renderSettingsForm() Render add-on settings form
 * @method static void   renderSettingsMenu() Render tab in settings page
 * @method static void   renderSmsNotifications( Backend\Modules\Notifications\Forms\Notifications $form ) Render SMS notification(s)
 * @method static void   renderStaffForm( Lib\Entities\Staff $staff ) Render Staff form tab details
 * @method static void   renderStaffService( int $staff_id, Lib\Entities\Service $service, array $services_data, array $attributes = array() ) Render controls for Staff on tab services.
 * @method static void   renderStaffServices( int $staff_id ) Render Components for staff profile
 * @method static void   renderStaffServiceTail( int $staff_id, Lib\Entities\Service $service, $attributes = array() ) Render controls for Staff on tab services.
 * @method static void   renderStaffTab( Lib\Entities\Staff $staff )
 * @method static void   renderTinyMceComponent() Render PopUp windows for WordPress editor.
 * @method static void   renderUrlSettings() Render URL settings on Settings page.
 * @method static array  saveSettings( array $alert, string $tab, $_post ) Save add-on settings
 * @method static array  serviceCreated( Lib\Entities\Service $service, array $_post ) Service created
 * @method static void   serviceDeleted( int $service_id ) Service deleted
 * @method static bool   showAppearanceCreditCard( bool $required ) In case there are payment systems that request credit card information in the Details step, it will return true.
 * @method static bool   showPaymentSpecificPrices( bool $show ) Say if need show price for each payment system.
 * @method static array  updateService( array $alert, Lib\Entities\Service $service, array $_post ) Update service settings in add-ons.
 * @method static void   updateStaff( array $_post ) Update staff settings in add-ons.
 * @method static void   updateStaffServices( array $_post ) Update staff services settings in add-ons.
 */
abstract class Shared extends Lib\Base\ProxyInvoker
{
}
