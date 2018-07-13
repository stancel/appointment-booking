<?php
namespace Bookly\Frontend\Components\Fields;

use Bookly\Lib;

/**
 * Class Address
 * @package Bookly\Frontend\Components\Fields
 */
class Address extends Lib\Base\Component
{
    /**
     * Render inputs for address fields on the frontend.
     *
     * @param Lib\UserBookingData $user_data
     */
    public static function render( Lib\UserBookingData $user_data )
    {
        foreach ( Lib\Utils\Common::getDisplayedAddressFields() as $field_name => $field ) {
            $field_value = $user_data->getAddressField( $field_name );
            self::renderTemplate( 'address',
                compact( 'field_name', 'field_value' )
            );
        }
    }
}