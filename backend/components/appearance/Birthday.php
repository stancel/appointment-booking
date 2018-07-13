<?php
namespace Bookly\Backend\Components\Appearance;

use Bookly\Lib;
use Bookly\Frontend;

/**
 * Class Birthday
 * @package Bookly\Backend\Components\Appearance
 */
class Birthday extends Frontend\Components\Fields\Birthday
{
    /**
     * Render triple select for birthday field in Appearance.
     *
     * @param Lib\UserBookingData $user_data
     */
    public static function render( Lib\UserBookingData $user_data = null )
    {
        // Render HTML.
        foreach ( Lib\Utils\DateTime::getDatePartsOrder() as $type ) {
            self::_renderEditableField( $type );
        }
    }

    /**
     * Render single editable field of given type.
     *
     * @param string $type
     */
    protected static function _renderEditableField( $type )
    {
        $editable = array( 'bookly_l10n_label_birthday_' . $type, 'bookly_l10n_option_' . $type, 'bookly_l10n_required_' . $type );
        $empty    = get_option( 'bookly_l10n_option_' . $type );
        $options  = array();

        switch ( $type ) {
            case 'day':
                $editable[] = 'bookly_l10n_invalid_day';
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
            compact( 'type', 'editable', 'empty', 'options' )
        );
    }
}