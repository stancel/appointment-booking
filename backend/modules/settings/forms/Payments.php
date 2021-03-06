<?php
namespace Bookly\Backend\Modules\Settings\Forms;

use Bookly\Lib;
use Bookly\Backend\Modules\Settings\Proxy;

/**
 * Class Payments
 * @package Bookly\Backend\Modules\Settings
 */
class Payments extends Lib\Base\Form
{
    /**
     * @inheritdoc
     */
    public function bind( array $_post, array $files = array() )
    {
        $fields = Proxy\Shared::preparePaymentOptions( array(
            'bookly_pmt_order',
            'bookly_pmt_currency',
            'bookly_pmt_price_format',
            'bookly_pmt_local',
            'bookly_paypal_enabled',
            'bookly_paypal_api_username',
            'bookly_paypal_api_password',
            'bookly_paypal_api_signature',
            'bookly_paypal_sandbox',
            'bookly_paypal_increase',
            'bookly_paypal_addition',
            'bookly_paypal_send_tax',
        ) );

        $this->setFields( $fields );
        parent::bind( $_post, $files );
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        foreach ( $this->data as $field => $value ) {
            update_option( $field, trim( $value ) );
        }
    }

}