<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/** @var Bookly\Lib\CartInfo $cart_info */
use Bookly\Lib\Proxy;
use Bookly\Lib\Utils\Common;
use Bookly\Lib\Utils\Price;

echo $progress_tracker;
echo $coupon_html;
?>

<div class="bookly-payment-nav">
    <div class="bookly-box"><?php echo $info_text ?></div>
    <div class="bookly-box bookly-list" style="display: none">
        <input type="radio" class="bookly-js-coupon-free" name="payment-method-<?php echo $form_id ?>" value="coupon" />
    </div>
    <?php foreach ( $payments_data as $payment_data ) : ?>
        <?php echo $payment_data ?>
    <?php endforeach ?>
</div>

<?php Proxy\RecurringAppointments::renderInfoMessage( $userData ) ?>

<?php if ( $pay_local ) : ?>
    <div class="bookly-gateway-buttons pay-local bookly-box bookly-nav-steps">
        <button class="bookly-back-step bookly-js-back-step bookly-btn ladda-button" data-style="zoom-in"  data-spinner-size="40">
            <span class="ladda-label"><?php echo Common::getTranslatedOption( 'bookly_l10n_button_back' ) ?></span>
        </button>
        <button class="bookly-next-step bookly-js-next-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
            <span class="ladda-label"><?php echo Common::getTranslatedOption( 'bookly_l10n_step_payment_button_next' ) ?></span>
        </button>
    </div>
<?php endif ?>

<?php if ( $pay_paypal ) : ?>
    <div class="bookly-gateway-buttons pay-paypal bookly-box bookly-nav-steps" style="display:none">
        <?php if ( $pay_paypal === Bookly\Lib\Payment\PayPal::TYPE_EXPRESS_CHECKOUT ) :
            Bookly\Lib\Payment\PayPal::renderECForm( $form_id );
        elseif ( $pay_paypal === Bookly\Lib\Payment\PayPal::TYPE_PAYMENTS_STANDARD ) :
            Proxy\PaypalPaymentsStandard::renderPaymentForm( $form_id, $page_url );
        endif ?>
    </div>
<?php endif ?>

<div class="bookly-gateway-buttons pay-card bookly-box bookly-nav-steps" style="display:none">
    <button class="bookly-back-step bookly-js-back-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
        <span class="ladda-label"><?php echo Common::getTranslatedOption( 'bookly_l10n_button_back' ) ?></span>
    </button>
    <button class="bookly-next-step bookly-js-next-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
        <span class="ladda-label"><?php echo Common::getTranslatedOption( 'bookly_l10n_step_payment_button_next' ) ?></span>
    </button>
</div>

<?php Proxy\Shared::renderPaymentGatewayForm( $form_id, $page_url ) ?>

<div class="bookly-gateway-buttons pay-coupon bookly-box bookly-nav-steps" style="display: none">
    <button class="bookly-back-step bookly-js-back-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
        <span class="ladda-label"><?php echo Common::getTranslatedOption( 'bookly_l10n_button_back' ) ?></span>
    </button>
    <button class="bookly-next-step bookly-js-next-step bookly-js-coupon-payment bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
        <span class="ladda-label"><?php echo Common::getTranslatedOption( 'bookly_l10n_step_payment_button_next' ) ?></span>
    </button>
</div>
