<?php
namespace Bookly\Lib\Widgets;

use Bookly\Lib;

/**
 * Class Widgets
 * @package Bookly\Lib\Widgets
 */
class Address extends Lib\Base\Components
{

    /**
     * Render inputs for address fields on the frontend.
     *
     * @param Lib\UserBookingData $user_data
     */
    public static function renderFrontendWidget( Lib\UserBookingData $user_data )
    {
        $address_show_fields = self::getDisplayedAddressFields();

        foreach ( $address_show_fields as $field_name => $field ) {
            $field_value = $user_data->getAddressField( $field_name );
            self::getInstance()->render( '_address_field',
                compact( 'field_name', 'field_value' )
            );
        }
    }

    /**
     * Render inputs for address fields in appearance.
     */
    public static function renderAppearanceWidget( )
    {
        $address_show_fields = self::getDisplayedAddressFields();
        $address_is_required = Lib\Config::addressRequired();

        foreach ( $address_show_fields as $field_name => $field ) {
            $labels = array( 'bookly_l10n_label_' . $field_name );
            if ( $address_is_required ) {
                $labels[] = 'bookly_l10n_required_' . $field_name;
            }
            $id = 'bookly-js-address-' . $field_name;

            self::getInstance()->render( '_address_editable_field',
                compact( 'id', 'labels' )
            );
        }
    }

    /**
     * Render inputs for address fields in settings.
     */
    public static function renderSettingsWidget( )
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
            $label = isset( $address_fields[ $field_name ] ) ? $address_fields[ $field_name ] : '';
            self::getInstance()->render( '_address_setting_field',
                compact( 'field_name', 'label', 'showed' )
            );
        }
    }

    /**
     * @return array
     */
    private static function getDisplayedAddressFields()
    {
        $address_show_fields = (array) get_option( 'bookly_cst_address_show_fields', array() );

        return array_filter( $address_show_fields, function( $field ) {
            return !( is_array( $field ) && array_key_exists( 'show', $field ) && !$field['show'] );
        } );
    }
}