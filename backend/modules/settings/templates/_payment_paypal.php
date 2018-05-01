<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Lib\Utils\Common;
use Bookly\Lib\Proxy;
?>
<div class="panel panel-default bookly-js-collapse" data-slug="paypal">
    <div class="panel-heading">
        <i class="bookly-js-handle bookly-margin-right-sm bookly-icon bookly-icon-draghandle bookly-cursor-move ui-sortable-handle" title="<?php esc_attr_e( 'Reorder', 'bookly' ) ?>"></i>
        <a href="#bookly_pmt_paypal" class="panel-title" role="button" data-toggle="collapse">
            PayPal
        </a>
        <img style="margin-left: 10px; float: right" src="<?php echo plugins_url( 'frontend/resources/images/paypal.png', \Bookly\Lib\Plugin::getMainFile() ) ?>" />
    </div>
    <div id="bookly_pmt_paypal" class="panel-collapse collapse in">
        <div class="panel-body">
            <div class="form-group">
                <?php Common::optionToggle( 'bookly_paypal_enabled', null, null,
                    Proxy\PaypalPaymentsStandard::prepareToggleOptions( array(
                        array( '0', __( 'Disabled', 'bookly' ) ),
                        array( Bookly\Lib\Payment\PayPal::TYPE_EXPRESS_CHECKOUT, 'PayPal Express Checkout' ),
                    ) )
                ) ?>
            </div>
            <div class="bookly-paypal">
                <div class="bookly-paypal-ec">
                    <?php Common::optionText( 'bookly_paypal_api_username',  __( 'API Username', 'bookly' ) ) ?>
                    <?php Common::optionText( 'bookly_paypal_api_password',  __( 'API Password', 'bookly' ) ) ?>
                    <?php Common::optionText( 'bookly_paypal_api_signature', __( 'API Signature', 'bookly' ) ) ?>
                </div>
                <?php Proxy\PaypalPaymentsStandard::renderSetUpOptions() ?>
                <?php Common::optionToggle( 'bookly_paypal_sandbox', __( 'Sandbox Mode', 'bookly' ), null, array( array( 1, __( 'Yes', 'bookly' ) ), array( 0, __( 'No', 'bookly' ) ) ) ) ?>
                <?php Bookly\Backend\Modules\Settings\Components::getInstance()->renderPaymentMethodTax( 'paypal' ) ?>
                <?php Bookly\Backend\Modules\Settings\Components::getInstance()->renderPaymentMethodPriceCorrection( 'paypal' ) ?>
            </div>
        </div>
    </div>
</div>