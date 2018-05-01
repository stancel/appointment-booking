<?php
namespace Bookly\Lib\Widgets;

use Bookly\Lib;

/**
 * Class Widgets
 * @package Bookly\Lib\Widgets
 */
class Birthday extends Lib\Base\Components
{
    /**
     * Render triple select for birthday field on the frontend.
     *
     * @param Lib\UserBookingData $user_data
     */
    public static function renderFrontendWidget( Lib\UserBookingData $user_data )
    {
        $widget = self::getInstance();
        $values = array( 'day' => '', 'month' => '', 'year' => '' );

        // Selected values.
        $birthday = $user_data->getBirthday();
        if ( $birthday != '' ) {
            $timestamp = strtotime( $birthday );
            $values['day']   = date( 'j', $timestamp );
            $values['month'] = date( 'n', $timestamp );
            $values['year']  = date( 'Y', $timestamp );
        }

        // Render HTML.
        foreach ( Lib\Utils\DateTime::getDatePartsOrder() as $type ) {
            $widget->_renderField( $type, $values[ $type ] );
        }
    }

    /**
     * Render triple select for birthday field in Appearance.
     */
    public static function renderAppearanceWidget()
    {
        $widget = self::getInstance();

        // Render HTML.
        foreach ( Lib\Utils\DateTime::getDatePartsOrder() as $type ) {
            $widget->_renderEditableField( $type );
        }
    }

    /**
     * Render single field of given type.
     *
     * @param string $type
     * @param string $selected_value
     */
    protected function _renderField( $type, $selected_value )
    {
        $title   = Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_birthday_' . $type );
        $empty   = Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_option_' . $type );
        $options = array();

        switch ( $type ) {
            case 'day':
                $options = $this->_dayOptions();
                break;
            case 'month':
                $options = $this->_monthOptions();
                break;
            case 'year':
                $options = $this->_yearOptions();
                break;
        }

        $this->render(
            '_birthday_field',
            compact( 'type', 'selected_value', 'title', 'empty', 'options' )
        );
    }

    /**
     * Render single editable field of given type.
     *
     * @param string $type
     */
    protected function _renderEditableField( $type )
    {
        $editable = array( 'bookly_l10n_label_birthday_' . $type, 'bookly_l10n_option_' . $type, 'bookly_l10n_required_' . $type );
        $empty    = get_option( 'bookly_l10n_option_' . $type );
        $options  = array();

        switch ( $type ) {
            case 'day':
                $editable[] = 'bookly_l10n_invalid_day';
                $options = $this->_dayOptions();
                break;
            case 'month':
                $options = $this->_monthOptions();
                break;
            case 'year':
                $options = $this->_yearOptions();
                break;
        }

        $this->render(
            '_birthday_editable_field',
            compact( 'type', 'editable', 'empty', 'options' )
        );
    }

    /**
     * Create day options.
     *
     * @return array
     */
    protected function _dayOptions()
    {
        return array_combine( range( 1, 31 ), range( 1, 31 ) );
    }

    /**
     * Create month options.
     *
     * @return array
     */
    protected function _monthOptions()
    {
        global $wp_locale;

        return array_combine( range( 1, 12 ), $wp_locale->month );
    }

    /**
     * Create year options.
     *
     * @return array
     */
    protected function _yearOptions()
    {
        $year  = (int) Lib\Slots\DatePoint::now()->format( 'Y' );
        $range = range( $year, $year - 100 );

        return array_combine( $range, $range );
    }
}