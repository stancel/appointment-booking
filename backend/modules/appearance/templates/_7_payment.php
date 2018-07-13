<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Backend\Components\Appearance\Codes;
use Bookly\Backend\Components\Appearance\Editable;
use Bookly\Backend\Modules\Appearance\Proxy;
?>
<div class="bookly-form">
    <?php include '_progress_tracker.php' ?>

    <?php Proxy\Coupons::renderCouponBlock() ?>
    <?php Proxy\DepositPayments::renderAppearance() ?>

    <div class="bookly-payment-nav">
        <div class="bookly-box bookly-js-payment-single-app">
            <?php Editable::renderText( 'bookly_l10n_info_payment_step_single_app', Codes::getHtml( 7 ), 'right' ) ?>
        </div>
        <div class="bookly-box bookly-js-payment-several-apps" style="display:none">
            <?php Editable::renderText( 'bookly_l10n_info_payment_step_several_apps', Codes::getHtml( 7, true ), 'right' ) ?>
        </div>

        <div class="bookly-box bookly-list">
            <label>
                <input type="radio" name="payment" checked="checked" />
                <?php Editable::renderString( array( 'bookly_l10n_label_pay_locally', ) ) ?>
            </label>
        </div>

        <div class="bookly-box bookly-list">
            <label>
                <input type="radio" name="payment" />
                <?php Editable::renderString( array( 'bookly_l10n_label_pay_paypal', ) ) ?>
                <img src="<?php echo plugins_url( 'frontend/resources/images/paypal.png', \Bookly\Lib\Plugin::getMainFile() ) ?>" alt="paypal" />
            </label>
        </div>

        <div class="bookly-box bookly-list"
            <?php if ( Proxy\Shared::showCreditCard( false ) == false ) : ?>
             style="display: none"
            <?php endif ?>
        >
            <label>
                <input type="radio" name="payment" id="bookly-card-payment" />
                <?php Editable::renderString( array( 'bookly_l10n_label_pay_ccard', ) ) ?>
                <img src="<?php echo plugins_url( 'frontend/resources/images/cards.png', \Bookly\Lib\Plugin::getMainFile() ) ?>" alt="cards" />
            </label>
            <form class="bookly-card-form bookly-clear-bottom" style="margin-top:15px;display: none;">
                <?php include '_card_payment.php' ?>
            </form>
        </div>

        <?php Proxy\Shared::renderPaymentGatewaySelector() ?>
    </div>

    <?php Proxy\RecurringAppointments::renderInfoMessage() ?>

    <div class="bookly-box bookly-nav-steps">
        <div class="bookly-back-step bookly-js-back-step bookly-btn">
            <?php Editable::renderString( array( 'bookly_l10n_button_back' ) ) ?>
        </div>
        <div class="bookly-next-step bookly-js-next-step bookly-btn">
            <?php Editable::renderString( array( 'bookly_l10n_step_payment_button_next' ) ) ?>
        </div>
    </div>
</div>