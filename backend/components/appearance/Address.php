<?php
namespace Bookly\Backend\Components\Appearance;

use Bookly\Lib;

/**
 * Class Address
 * @package Bookly\Backend\Components\Appearance
 */
class Address extends Lib\Base\Ajax
{
    /**
     * Render inputs for address fields in appearance.
     */
    public static function render()
    {
        $address_is_required = Lib\Config::addressRequired();

        foreach ( Lib\Utils\Common::getDisplayedAddressFields() as $field_name => $field ) {
            $labels = array( 'bookly_l10n_label_' . $field_name );
            if ( $address_is_required ) {
                $labels[] = 'bookly_l10n_required_' . $field_name;
            }
            $id = 'bookly-js-address-' . $field_name;

            self::renderTemplate( 'address',
                compact( 'id', 'labels' )
            );
        }
    }
}