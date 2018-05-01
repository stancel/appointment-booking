<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Lib\Utils\Render;
use Bookly\Lib\Entities\Payment;
?>
<label for="bookly_<?php echo $system ?>_discount"><?php _e( 'Price correction', 'bookly' ) ?></label>
<?php if ( ! in_array( $system, array( Payment::TYPE_MOLLIE, Payment::TYPE_PAYSON, Payment::TYPE_STRIPE ) ) ) :
    Bookly\Lib\Proxy\Taxes::renderPaymentTaxHelpMessage();
endif ?>
<div class="form-group">
    <div class="row">
        <div class="col-md-6">
            <?php Render::numericInput( 'bookly_' . $system . '_increase', __( 'Increase/Discount (%)', 'bookly' ), null, -100, 'any', 100 ) ?>
        </div>
        <div class="col-md-6">
            <?php Render::numericInput( 'bookly_' . $system . '_addition', __( 'Addition/Deduction', 'bookly' ), null, null, 'any' ) ?>
        </div>
    </div>
</div>