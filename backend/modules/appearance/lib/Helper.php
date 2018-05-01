<?php
namespace Bookly\Backend\Modules\Appearance\Lib;

class Helper
{
    /**
     * Render editable string (single line).
     *
     * @param array $options
     * @param bool $return
     * @return string
     */
    public static function renderString( array $options, $return = false )
    {
        return self::_renderEditable( $options, 'span', $return );
    }

    /**
     * Render editable label.
     *
     * @param array $options
     * @param bool $return
     * @return string
     */
    public static function renderLabel( array $options, $return = false )
    {
        return self::_renderEditable( $options, 'label', $return );
    }

    /**
     * Render editable text (multi-line).
     *
     * @param string $option_name
     * @param string $codes
     * @param string $placement
     * @param string $title
     */
    public static function renderText( $option_name, $codes = '', $placement = 'bottom', $title = '' )
    {
        $option_value = get_option( $option_name );

        printf( '<span class="bookly-js-editable bookly-js-option %s editable-pre-wrapped" data-type="bookly" data-fieldType="textarea" data-values="%s" data-codes="%s" data-title="%s" data-placement="%s">%s</span>',
            $option_name,
            esc_attr( json_encode( array( $option_name => $option_value ) ) ),
            esc_attr( $codes ),
            esc_attr( $title ),
            $placement,
            esc_html( $option_value )
        );
    }

    /**
     * Render editable element.
     *
     * @param array $options
     * @param string $tag
     * @param bool $return
     * @return string
     */
    private static function _renderEditable( array $options, $tag, $return = false )
    {
        $data = array();
        foreach ( $options as $option_name ) {
            $data[ $option_name ] = get_option( $option_name );
        }

        $class = $options[0];
        $data_values = esc_attr( json_encode( $data ) );
        $content = esc_html( $data[ $options[0] ] );

        $template = '<{tag} class="bookly-js-editable bookly-js-option {class}" data-type="bookly" data-values="{data-values}">{content}</{tag}>';
        $html = strtr( $template, array(
            '{tag}'         => $tag,
            '{class}'       => $class,
            '{data-values}' => $data_values,
            '{content}'     => $content,
        ) );

        if ( $return ) {
            return $html;
        }

        echo $html;
    }
}