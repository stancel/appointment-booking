<?php
namespace Bookly\Backend\Modules\Customers;

use Bookly\Lib;

/**
 * Class Page
 * @package Bookly\Backend\Modules\Customers
 */
class Page extends Lib\Base\Component
{
    /**
     * Render page.
     */
    public static function render()
    {
        if ( self::hasParameter( 'import-customers' ) ) {
            self::_importCustomers();
        }

        self::enqueueStyles( array(
            'backend'  => array( 'bootstrap/css/bootstrap-theme.min.css', ),
            'frontend' => array( 'css/ladda.min.css', ),
        ) );

        self::enqueueScripts( array(
            'backend' => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/datatables.min.js' => array( 'jquery' ),
            ),
            'frontend' => array(
                'js/spin.min.js' => array( 'jquery' ),
                'js/ladda.min.js' => array( 'jquery' ),
            ),
            'module' => array(
                'js/customers.js' => array( 'bookly-datatables.min.js', 'bookly-ng-customer.js' ),
            ),
        ) );

        // Customer information fields.
        $info_fields = (array) Lib\Proxy\CustomerInformation::getFieldsWhichMayHaveData();

        wp_localize_script( 'bookly-customers.js', 'BooklyL10n', array(
            'csrfToken'       => Lib\Utils\Common::getCsrfToken(),
            'first_last_name' => (int) Lib\Config::showFirstLastName(),
            'groupsActive'    => (int) Lib\Config::customerGroupsActive(),
            'infoFields'      => $info_fields,
            'edit'            => __( 'Edit', 'bookly' ),
            'are_you_sure'    => __( 'Are you sure?', 'bookly' ),
            'wp_users'        => get_users( array( 'fields' => array( 'ID', 'display_name' ), 'orderby' => 'display_name' ) ),
            'zeroRecords'     => __( 'No customers found.', 'bookly' ),
            'processing'      => __( 'Processing...', 'bookly' ),
            'edit_customer'   => __( 'Edit customer', 'bookly' ),
            'new_customer'    => __( 'New customer', 'bookly' ),
            'create_customer' => __( 'Create customer', 'bookly' ),
            'save'            => __( 'Save', 'bookly' ),
            'search'          => __( 'Quick search customer', 'bookly' ),
        ) );

        self::renderTemplate( 'index', compact( 'info_fields' ) );
    }

    /**
     * Import customers from CSV.
     */
    private static function _importCustomers()
    {
        @ini_set( 'auto_detect_line_endings', true );
        $fields = array();
        foreach ( array( 'full_name', 'first_name', 'last_name', 'phone', 'email', 'birthday' ) as $field ) {
            if ( self::parameter( $field ) ) {
                $fields[] = $field;
            }
        }
        $file = fopen( $_FILES['import_customers_file']['tmp_name'], 'r' );
        while ( $line = fgetcsv( $file, null, self::parameter( 'import_customers_delimiter' ) ) ) {
            if ( $line[0] != '' ) {
                $customer = new Lib\Entities\Customer();
                foreach ( $line as $number => $value ) {
                    if ( $number < count( $fields ) ) {
                        if ( $fields[ $number ] == 'birthday' ) {
                            $dob = date_create( $value );
                            if ( $dob !== false ) {
                                $customer->setBirthday( $dob->format( 'Y-m-d' ) );
                            }
                        } else {
                            $method = 'set' . implode( '', array_map( 'ucfirst', explode( '_', $fields[ $number ] ) ) );
                            $customer->$method( $value );
                        }
                    }
                }
                $customer->save();
            }
        }
    }
}