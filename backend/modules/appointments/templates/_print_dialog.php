<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Lib\Utils\Common;
use Bookly\Lib\Config;
use Bookly\Backend\Modules\Appointments\Proxy as AppointmentsProxy;
?>
<div id="bookly-print-dialog" class="modal fade" tabindex=-1 role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><?php _e( 'Print', 'bookly' ) ?></h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <?php $column = 0 ?>
                    <div class="checkbox"><label><input checked value="<?php echo $column ++ ?>" type="checkbox"/><?php _e( 'No.', 'bookly' ) ?></label></div>
                    <div class="checkbox"><label><input checked value="<?php echo $column ++ ?>" type="checkbox"/><?php _e( 'Appointment Date', 'bookly' ) ?></label></div>
                    <div class="checkbox"><label><input checked value="<?php echo $column ++ ?>" type="checkbox"/><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_employee' ) ?></label></div>
                    <div class="checkbox"><label><input checked value="<?php echo $column ++ ?>" type="checkbox"/><?php _e( 'Customer Name', 'bookly' ) ?></label></div>
                    <div class="checkbox"><label><input checked value="<?php echo $column ++ ?>" type="checkbox"/><?php _e( 'Customer Phone', 'bookly' ) ?></label></div>
                    <div class="checkbox"><label><input checked value="<?php echo $column ++ ?>" type="checkbox"/><?php _e( 'Customer Email', 'bookly' ) ?></label></div>
                    <?php if ( Config::groupBookingActive() ) : ?>
                        <div class="checkbox"><label><input checked value="<?php echo $column ++ ?>" type="checkbox"/><?php _e( 'Number of persons', 'bookly' ) ?></label></div>
                    <?php endif ?>
                    <div class="checkbox"><label><input checked value="<?php echo $column ++ ?>" type="checkbox"/><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_service' ) ?></label></div>
                    <div class="checkbox"><label><input checked value="<?php echo $column ++ ?>" type="checkbox"/><?php _e( 'Duration', 'bookly' ) ?></label></div>
                    <div class="checkbox"><label><input checked value="<?php echo $column ++ ?>" type="checkbox"/><?php _e( 'Status', 'bookly' ) ?></label></div>
                    <div class="checkbox"><label><input checked value="<?php echo $column ++ ?>" type="checkbox"/><?php _e( 'Payment', 'bookly' ) ?></label></div>
                    <?php if ( Config::ratingsActive() ) { AppointmentsProxy\Ratings::renderExport( $column++ ); } ?>
                    <?php if ( Config::showNotes() ) : ?>
                        <div class="checkbox"><label><input checked value="<?php echo $column ++ ?>" type="checkbox"/><?php echo esc_html( Common::getTranslatedOption( 'bookly_l10n_label_notes' ) ) ?></label></div>
                    <?php endif ?>
                    <?php foreach ( $custom_fields as $custom_field ) : ?>
                        <div class="checkbox"><label><input checked value="<?php echo $column ++ ?>" type="checkbox"/><?php echo $custom_field->label ?></label></div>
                    <?php endforeach ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-lg btn-success" id="bookly-print" data-dismiss="modal">
                    <?php _e( 'Print', 'bookly' ) ?>
                </button>
            </div>
        </div>
    </div>
</div>