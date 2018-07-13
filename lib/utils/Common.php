<?php
namespace Bookly\Lib\Utils;

use Bookly\Lib;

/**
 * Class Common
 * @package Bookly\Lib\Utils
 */
abstract class Common
{
    /** @var string CSRF token */
    private static $csrf = null;

    /**
     * Get e-mails of wp-admins
     *
     * @return array
     */
    public static function getAdminEmails()
    {
        return array_map(
            function ( $a ) {
                return $a->data->user_email;
            },
            get_users( 'role=administrator' )
        );
    } // getAdminEmails

    /**
     * Generates email's headers FROM: Sender Name < Sender E-mail >
     *
     * @param array $extra
     * @return array
     */
    public static function getEmailHeaders( $extra = array() )
    {
        $headers = array();
        if ( Lib\Config::sendEmailAsHtml() ) {
            $headers[] = 'Content-Type: text/html; charset=utf-8';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=utf-8';
        }
        $headers[] = 'From: ' . get_option( 'bookly_email_sender_name' ) . ' <' . get_option( 'bookly_email_sender' ) . '>';
        if ( isset ( $extra['reply-to'] ) ) {
            $headers[] = 'Reply-To: ' . $extra['reply-to']['name'] . ' <' . $extra['reply-to']['email'] . '>';
        }

        return apply_filters( 'bookly_email_headers', $headers );
    }

    /**
     * @return string
     */
    public static function getCurrentPageURL()
    {
        if ( ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) || $_SERVER['SERVER_PORT'] == 443 ) {
            $url = 'https://';
        } else {
            $url = 'http://';
        }
        $url .= isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST'];

        return $url . $_SERVER['REQUEST_URI'];
    }

    /**
     * Escape params for admin.php?page
     *
     * @param $page_slug
     * @param array $params
     * @return string
     */
    public static function escAdminUrl( $page_slug, $params = array() )
    {
        $path = 'admin.php?page=' . $page_slug;
        if ( ( $query = build_query( $params ) ) != '' ) {
            $path .= '&' . $query;
        }

        return esc_url( admin_url( $path ) );
    }

    /**
     * Get option translated with WPML.
     *
     * @param $option_name
     * @return string
     */
    public static function getTranslatedOption( $option_name )
    {
        return self::getTranslatedString( $option_name, get_option( $option_name ) );
    }

    /**
     * Get string translated with WPML.
     *
     * @param             $name
     * @param string      $original_value
     * @param null|string $language_code Return the translation in this language
     * @return string
     */
    public static function getTranslatedString( $name, $original_value = '', $language_code = null )
    {
        return apply_filters( 'wpml_translate_single_string', $original_value, 'bookly', $name, $language_code );
    }

    /**
     * Check whether the current user is administrator or not.
     *
     * @return bool
     */
    public static function isCurrentUserAdmin()
    {
        return current_user_can( 'manage_options' );
    }

    /**
     * @param int $duration
     * @return array
     */
    public static function getDurationSelectOptions( $duration )
    {
        $time_interval = get_option( 'bookly_gen_time_slot_length' );

        $options = array();

        for ( $j = $time_interval; $j <= 720; $j += $time_interval ) {

            if ( ( $duration / 60 > $j - $time_interval ) && ( $duration / 60 < $j ) ) {
                $options[] = array(
                    'value' => $duration,
                    'label' => DateTime::secondsToInterval( $duration ),
                    'selected' => 'selected',
                );
            }

            $options[] = array(
                'value' => $j * 60,
                'label' => DateTime::secondsToInterval( $j * 60 ),
                'selected' => selected( $duration, $j * 60, false ),
            );
        }

        for ( $j = 86400; $j <= 604800; $j += 86400 ) {
            $options[] = array(
                'value' => $j,
                'label' => DateTime::secondsToInterval( $j ),
                'selected' => selected( $duration, $j, false ),
            );
        }

        return $options;
    }

    /**
     * XOR encrypt/decrypt.
     *
     * @param string $str
     * @param string $password
     * @return string
     */
    private static function _xor( $str, $password = '' )
    {
        $len   = strlen( $str );
        $gamma = '';
        $n     = $len > 100 ? 8 : 2;
        while ( strlen( $gamma ) < $len ) {
            $gamma .= substr( pack( 'H*', sha1( $password . $gamma ) ), 0, $n );
        }

        return $str ^ $gamma;
    }

    /**
     * XOR encrypt with Base64 encode.
     *
     * @param string $str
     * @param string $password
     * @return string
     */
    public static function xorEncrypt( $str, $password = '' )
    {
        return base64_encode( self::_xor( $str, $password ) );
    }

    /**
     * XOR decrypt with Base64 decode.
     *
     * @param string $str
     * @param string $password
     * @return string
     */
    public static function xorDecrypt( $str, $password = '' )
    {
        return self::_xor( base64_decode( $str ), $password );
    }

    /**
     * Codes table helper
     *
     * @param array $codes
     * @param array $flags
     * @return string
     */
    public static function codes( array $codes, $flags = array() )
    {
        // Sort codes alphabetically.
        usort( $codes, function ( $code_a, $code_b ) {
            return strcmp( $code_a['code'], $code_b['code'] );
        } );

        $tbody = '';
        foreach ( $codes as $code ) {
            $valid = true;
            if ( isset ( $code['flags'] ) ) {
                foreach ( $code['flags'] as $flag => $value ) {
                    $valid = false;
                    if ( isset ( $flags[ $flag ] ) ) {
                        if ( is_string( $value ) && preg_match( '/([!>=<]+)(\d+)/', $value, $match ) ) {
                            switch ( $match[1] ) {
                                case '<':  $valid = $flags[ $flag ] < $match[2];  break;
                                case '<=': $valid = $flags[ $flag ] <= $match[2]; break;
                                case '=':  $valid = $flags[ $flag ] == $match[2]; break;
                                case '!=': $valid = $flags[ $flag ] != $match[2]; break;
                                case '>=': $valid = $flags[ $flag ] >= $match[2]; break;
                                case '>':  $valid = $flags[ $flag ] > $match[2];  break;
                            }
                        } else {
                            $valid = $flags[ $flag ] == $value;
                        }
                    }
                    if ( ! $valid ) {
                        break;
                    }
                }
            }
            if ( $valid ) {
                $tbody .= sprintf(
                    '<tr><td><input value="{%s}" readonly="readonly" onclick="this.select()" /> - %s</td></tr>',
                    $code['code'],
                    $code['description']
                );
            }
        }

        return '<table class="bookly-codes"><tbody>' . $tbody . '</tbody></table>';
    }

    /**
     * Generate unique value for entity field.
     *
     * @param string $entity_class_name
     * @param string $token_field
     * @return string
     */
    public static function generateToken( $entity_class_name, $token_field )
    {
        /** @var Lib\Base\Entity $entity */
        $entity = new $entity_class_name();
        do {
            $token = md5( uniqid( time(), true ) );
        }
        while ( $entity->loadBy( array( $token_field => $token ) ) === true );

        return $token;
    }


    /**
     * Get CSRF token.
     *
     * @return string
     */
    public static function getCsrfToken()
    {
        if ( self::$csrf === null ) {
            self::$csrf = wp_create_nonce( 'bookly' );
        }

        return self::$csrf;
    }

    /**
     * Set nocache constants.
     */
    public static function noCache()
    {
        if ( ! defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true );
        }
        if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
            define( 'DONOTCACHEOBJECT', true );
        }
        if ( ! defined( 'DONOTCACHEDB' ) ) {
            define( 'DONOTCACHEDB', true );
        }
    }

    /**
     * @return array
     */
    public static function getDisplayedAddressFields()
    {
        $address_show_fields = (array) get_option( 'bookly_cst_address_show_fields', array() );

        return array_filter( $address_show_fields, function( $field ) {
            return !( is_array( $field ) && array_key_exists( 'show', $field ) && !$field['show'] );
        } );
    }

    /**
     * @param array $data
     * @return string
     */
    public static function getFullAddressByCustomerData( array $data )
    {
        $address_show_fields = self::getDisplayedAddressFields();
        $address_fields = array();

        foreach ( $address_show_fields as $field_name => $field ) {

            if ( !array_key_exists( $field_name, $data ) ) {
                continue;
            }

            $address_fields[] = $data[ $field_name ];
        }

        return implode( ', ', array_filter( $address_fields ) );
    }
}