<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Backend\Components\Appearance\Editable;
/**
 * @var array $labels
 * @var string $id
 */
?>
<div class="bookly-box" id="<?php echo $id ?>">
    <div class="bookly-form-group">
        <?php Editable::renderLabel( $labels ) ?>
        <div>
            <input type="text" value="" maxlength="255" />
        </div>
    </div>
</div>