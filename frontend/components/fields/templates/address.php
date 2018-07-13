<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * @var string $field_name
 * @var string $value
 */
?>
<div class="bookly-box">
    <div class="bookly-form-group">
        <label><?php echo Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_' . $field_name ) ?></label>
        <div>
            <input type="text"
                   class="<?php echo 'bookly-js-address-' . $field_name ?>"
                   value="<?php echo esc_attr( $field_value  ) ?>"
                   maxlength="255" />
        </div>
        <div class="<?php echo 'bookly-js-address-' . $field_name . '-error' ?> bookly-label-error"></div>
    </div>
</div>
