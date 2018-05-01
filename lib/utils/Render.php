<?php
namespace Bookly\Lib\Utils;

/**
 * Class Render
 * @package Bookly\Lib\Utils
 */
class Render
{

    /******************************************************************************************************************
     * BACKEND                                                                                                        *
     ******************************************************************************************************************/

    /**
     * Render attachment media image
     *
     * @param string $option_name
     * @param string $class
     * @return string
     */
    public static function browseImage( $option_name, $class = 'lg' )
    {
        $img = wp_get_attachment_image_src( get_option( $option_name ), 'full' );
        return sprintf( '
            <div id="bookly-js-%1$s" class="bookly-thumb bookly-thumb-%2$s bookly-margin-right-lg">
                <input type="hidden" name="%1$s" data-default="%3$s" value="%3$s">
                <div class="bookly-flex-cell">
                    <div class="form-group">
                        <div class="bookly-js-image bookly-thumb bookly-thumb-%2$s bookly-margin-right-lg" style="%4$s" data-style="%4$s">
                            <a class="dashicons dashicons-trash text-danger bookly-thumb-delete" href="javascript:void(0)" style="%5$s" title="%6$s"></a>
                            <div class="bookly-thumb-edit">
                                <div class="bookly-pretty"><label class="bookly-pretty-indicator bookly-thumb-edit-btn">%7$s</label></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script type="application/javascript">
            jQuery(function ($) {
                $(\'#bookly-js-%1$s .bookly-pretty-indicator\').on(\'click\', function(){
                        var frame = wp.media({
                        library: {type: \'image\'},
                        multiple: false
                    });
                    frame.on(\'select\', function () {
                        var selection = frame.state().get(\'selection\').toJSON(),
                            img_src
                            ;
                        if (selection.length) {
                            if (selection[0].sizes[\'full\'] !== undefined) {
                                img_src = selection[0].sizes[\'full\'].url;
                            } else {
                                img_src = selection[0].url;
                            }
                            $(\'[name=%1$s]\').val(selection[0].id);
                            $(\'#bookly-js-%1$s .bookly-js-image\').css({\'background-image\': \'url(\' + img_src + \')\', \'background-size\': \'contain\'});
                            $(\'#bookly-js-%1$s .bookly-thumb-delete\').show();
                            $(this).hide();
                        }
                    });
                    frame.open();
                });
            
                $(\'#bookly-js-%1$s\')
                    .on(\'click\', \'.bookly-thumb-delete\', function () {
                        var $thumb = $(this).parents(\'.bookly-js-image\');
                        $thumb.attr(\'style\', \'\');
                        $(\'[name=%1$s]\').val(\'\');
                    });
            });
            </script>',
            $option_name,
            $class,
            get_option( $option_name ),
            $img ? 'background-image: url(' . $img[0] . '); background-size: contain;' : '',
            $img ? '' : 'display: none;',
            __( 'Delete', 'bookly' ),
            __( 'Image', 'bookly' )
        );
    }

    /**
     * Build container
     *
     * @param string $label
     * @param string $help
     * @param string $content
     */
    public static function container( $label, $help, $content )
    {
        $control = sprintf( '<div>%s</div>', $content );

        echo self::getSettingsTemplate( $label, null, $help, $control );
    }

    /**
     * Build control for numeric input option
     *
     * @param string   $option_name
     * @param string   $label
     * @param string   $help
     * @param int|null $min
     * @param int|null $step
     * @param int|null $max
     */
    public static function numericInput( $option_name, $label, $help, $min = 1, $step = 1, $max = null )
    {
        $control = sprintf( '<input type="number" class="form-control" name="%1$s" id="%1$s" value="%2$s"%3$s%4$s%5$s>',
            esc_attr( $option_name ),
            esc_attr( get_option( $option_name ) ),
            $min  !== null ? ' min="' . $min . '"' : '',
            $max  !== null ? ' max="' . $max . '"' : '',
            $step !== null ? ' step="' . $step . '"' : ''
        );

        echo self::getSettingsTemplate( $label, $option_name, $help, $control );
    }

    /**
     * Return html for setting option
     *
     * @param string $label
     * @param string $option_name
     * @param string $help
     * @param string $control
     * @return string
     */
    private static function getSettingsTemplate( $label, $option_name, $help, $control )
    {
        return strtr( '<div class="form-group">{label}{help}{control}</div>',
            array(
                '{label}'   => empty( $label ) ? '' : sprintf( '<label for="%s">%s</label>', $option_name, $label ),
                '{help}'    => empty( $help ) ? '' : sprintf( '<p class="help-block">%s</p>', $help ),
                '{control}' => $control,
            )
        );
    }

    /******************************************************************************************************************
     * FRONTEND                                                                                                       *
     ******************************************************************************************************************/

}