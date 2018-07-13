<?php
namespace Bookly\Frontend\Components\Fields;

use Bookly\Lib;

/**
 * Class Birthday
 * @package Bookly\Frontend\Components\Fields
 */
class Birthday extends Lib\Base\Component
{
    /**
     * Render triple select for birthday field on the frontend.
     *
     * @param Lib\UserBookingData $user_data
     */
    public static function render( Lib\UserBookingData $user_data )
    {
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
            self::_renderField( $type, $values[ $type ] );
        }
    }

    /**
     * Render triple select for birthday field on the frontend.
     *
     * @param string $birthday
     */
    public static function renderBootstrap( $birthday )
    {
        $values = array( 'day' => '', 'month' => '', 'year' => '' );

        // Selected values.
        if ( $birthday != '' ) {
            $timestamp = strtotime( $birthday );
            $values['day']   = date( 'j', $timestamp );
            $values['month'] = date( 'n', $timestamp );
            $values['year']  = date( 'Y', $timestamp );
        }

        // Render HTML.
        foreach ( Lib\Utils\DateTime::getDatePartsOrder() as $type ) {
            self::_renderFieldBootstrap( $type, $values[ $type ] );
        }
    }

    /**
     * Render single field of given type.
     *
     * @param string $type
     * @param string $selected_value
     */
    protected static function _renderField( $type, $selected_value )
    {
        $title   = Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_birthday_' . $type );
        $empty   = Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_option_' . $type );
        $options = array();

        switch ( $type ) {
            case 'day':
                $options = self::_dayOptions();
                break;
            case 'month':
                $options = self::_monthOptions();
                break;
            case 'year':
                $options = self::_yearOptions();
                break;
        }

        self::renderTemplate(
            'birthday',
            compact( 'type', 'selected_value', 'title', 'empty', 'options' )
        );
    }

    /**
     * Render single field of given type.
     *
     * @param string $type
     * @param string $selected_value
     */
    protected static function _renderFieldBootstrap( $type, $selected_value )
    {
        $title   = Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_birthday_' . $type );
        $empty   = Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_option_' . $type );
        $options = array();

        switch ( $type ) {
            case 'day':
                $options = self::_dayOptions();
                break;
            case 'month':
                $options = self::_monthOptions();
                break;
            case 'year':
                $options = self::_yearOptions();
                break;
        }

        self::renderTemplate(
            'birthday_bootstrap',
            compact( 'type', 'selected_value', 'title', 'empty', 'options' )
        );
    }

    /**
     * Create day options.
     *
     * @return array
     */
    protected static function _dayOptions()
    {
        return array_combine( range( 1, 31 ), range( 1, 31 ) );
    }

    /**
     * Create month options.
     *
     * @return array
     */
    protected static function _monthOptions()
    {
        global $wp_locale;

        return array_combine( range( 1, 12 ), $wp_locale->month );
    }

    /**
     * Create year options.
     *
     * @return array
     */
    protected static function _yearOptions()
    {
        $year  = (int) Lib\Slots\DatePoint::now()->format( 'Y' );
        $range = range( $year, $year - 100 );

        return array_combine( $range, $range );
    }
}