<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * @var string $field_name
 * @var string $label
 * @var bool $showed
 */
?>
<div class="bookly-flexbox">
    <div class="bookly-flex-cell">
        <i class="bookly-js-handle bookly-margin-right-sm bookly-icon bookly-icon-draghandle bookly-cursor-move"
           title="<?php esc_attr_e( 'Reorder', 'bookly' ) ?>">
        </i>
    </div>
    <div class="bookly-flex-cell" style="width: 100%">
        <div class="checkbox">
            <label>
                <input type="hidden" name="bookly_cst_address_show_fields[<?php echo $field_name ?>][show]" value="0">
                <input type="checkbox"
                       name="bookly_cst_address_show_fields[<?php echo $field_name ?>][show]"
                       value="1" <?php checked( $showed, true ) ?>>
                <?php echo $label ?>
            </label>
        </div>
    </div>
</div>