<?php
use \Bookly\Backend\Modules\Appearance\Lib\Helper;
/**
 * @var array $labels
 * @var string $id
 */
?>
<div class="bookly-box" id="<?php echo $id ?>">
    <div class="bookly-form-group">
        <?php Helper::renderLabel( $labels ) ?>
        <div>
            <input type="text" value="" maxlength="255" />
        </div>
    </div>
</div>