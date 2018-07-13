<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Backend\Components\Appearance\Editable;
?>

<div class="bookly-form-group">
    <?php Editable::renderLabel( $editable ) ?>
    <div>
        <select class="bookly-js-select-birthday-<?php echo $type ?>">
            <option value="" class="bookly-js-option bookly_l10n_option_<?php echo $type ?>"><?php echo esc_html( $empty ) ?></option>
            <?php foreach ( $options as $value => $option ) : ?>
                <option value="<?php echo $value ?>"><?php echo esc_html( $option ) ?></option>
            <?php endforeach ?>
        </select>
    </div>
</div>