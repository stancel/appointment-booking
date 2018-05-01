<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Lib\Utils\Common;
use Bookly\Lib\Utils\Price;
?>
<div class="bookly-box bookly-list">
    <label>
        <input type="radio" class="bookly-payment" name="payment-method-<?php echo $form_id ?>" value="paypal"/>
        <span><?php echo Common::getTranslatedOption( 'bookly_l10n_label_pay_paypal' ) ?>
            <?php if ( $show_pay_now ) : ?>
                <?php
                $paypal = clone $cart_info;
                $paypal->setPriceCorrection( get_option( 'bookly_paypal_increase' ), get_option( 'bookly_paypal_addition' ) );
                ?>
                <span class="bookly-js-pay"><?php echo Price::format( $paypal->getPayNow() ) ?></span>
            <?php endif ?>
        </span>
        <img src="<?php echo plugins_url( 'frontend/resources/images/paypal.png', \Bookly\Lib\Plugin::getMainFile() ) ?>" alt="PayPal" />
    </label>
    <?php if ( $payment['gateway'] == \Bookly\Lib\Entities\Payment::TYPE_PAYPAL && $payment['status'] == 'error' ) : ?>
        <div class="bookly-label-error"><?php echo $payment['data'] ?></div>
    <?php endif ?>
</div>