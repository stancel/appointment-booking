<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Backend\Components\Settings;
use Bookly\Lib\Entities\Payment;
?>
<label for="bookly_<?php echo $gateway ?>_discount"><?php _e( 'Price correction', 'bookly' ) ?></label>
<?php if ( ! in_array( $gateway, array( Payment::TYPE_MOLLIE, Payment::TYPE_PAYSON, Payment::TYPE_STRIPE, Payment::TYPE_PAYUBIZ ) ) ) :
    Settings\Proxy\Taxes::renderHelpMessage();
endif ?>
<div class="form-group">
    <div class="row">
        <div class="col-md-6">
            <?php Settings\Inputs::renderNumber( 'bookly_' . $gateway . '_increase', __( 'Increase/Discount (%)', 'bookly' ), '', -100, null, 100 ) ?>
        </div>
        <div class="col-md-6">
            <?php Settings\Inputs::renderNumber( 'bookly_' . $gateway . '_addition', __( 'Addition/Deduction', 'bookly' ), '' ) ?>
        </div>
    </div>
</div>