<?php
namespace Bookly\Frontend\Modules\Booking\Proxy;

use Bookly\Lib;

/**
 * Class Shared
 * @package Bookly\Frontend\Modules\Booking\Proxy
 *
 * @method static void   enqueueBookingAssets() Enqueue assets for booking form.
 * @method static string getExtrasStepHtml( Lib\UserBookingData $userData, $show_cart_btn, $info_text, $progress_tracker ) Get Extras step HTML.
 * @method static string renderCustomFieldsOnDetailsStep( Lib\UserBookingData $userData ) Get Custom Fields HTML for details step.
 * @method static array  prepareBookingErrorCodes( array $errors ) Prepare array for errors on booking steps.
 * @method static array  prepareCartItemInfoText( array $data, Lib\CartItem $cart_item ) Prepare array for replacing in Cart items.
 * @method static array  prepareChainItemInfoText( array $data, Lib\ChainItem $chain_item ) Prepare array for replacing in Chain items.
 * @method static array  prepareInfoTextCodes( array $info_text_codes, array $data ) Prepare array for replacing on booking steps.
 * @method static array  preparePaymentGatewaySelector( array $payment_data, $form_id, array $payment, Lib\CartInfo $cart_info, bool $show_price ) Prepare gateway selector on step Payment.
 * @method static void   printBookingAssets() Print assets for booking form.
 * @method static void   renderCartItemInfo( Lib\UserBookingData $userData, $cart_key, $positions, $desktop ) Render extra info for cart item at Cart step.
 * @method static void   renderChainItemHead() Render head for chain in step service.
 * @method static void   renderChainItemTail() Render tail for chain in step service.
 * @method static void   renderChainItemTailTip() Render tail tip for chain in step service.
 * @method static void   renderPaymentGatewayForm( $form_id, $page_url ) Render gateway form on step Payment.
 * @method static void   renderWaitingListInfoText() Render WL info text in Time step.
 * @method static void   renderCartDiscountRow( array $table, string $layout ) Render "Group Discount" row on a Cart step
 */
abstract class Shared extends Lib\Base\Proxy
{

}