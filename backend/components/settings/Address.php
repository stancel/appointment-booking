<?php
namespace Bookly\Backend\Components\Settings;

use Bookly\Lib;

/**
 * Class Address
 * @package Bookly\Backend\Components\Settings
 */
class Address extends Lib\Base\Component
{
    /**
     * Render inputs for address fields in settings.
     */
    public static function render()
    {
        $address_show_fields = (array) get_option( 'bookly_cst_address_show_fields' );
        $address_fields = array(
            'country'            => get_option( 'bookly_l10n_label_country' ),
            'state'              => get_option( 'bookly_l10n_label_state' ),
            'postcode'           => get_option( 'bookly_l10n_label_postcode' ),
            'city'               => get_option( 'bookly_l10n_label_city' ),
            'street'             => get_option( 'bookly_l10n_label_street' ),
            'additional_address' => get_option( 'bookly_l10n_label_additional_address' ),
        );

        foreach ( $address_show_fields as $field_name => $attributes ) {
            $showed = (bool) $attributes['show'];
            $label = isset ( $address_fields[ $field_name ] ) ? $address_fields[ $field_name ] : '';

            self::renderTemplate( 'address', compact( 'field_name', 'label', 'showed' ) );
        }
    }
}