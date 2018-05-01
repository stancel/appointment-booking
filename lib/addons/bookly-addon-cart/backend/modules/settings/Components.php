<?php
namespace BooklyCart\Backend\Modules\Settings;

use Bookly\Lib as BooklyLib;

/**
 * Class Components
 * @package BooklyCart\Backend\Modules\Settings
 */
class Components extends BooklyLib\Base\Components
{
    /**
     * Render settings form.
     *
     * @throws \Exception
     */
    public function renderSettingsForm()
    {
        $cart_columns = array(
            'service'  => BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_service' ),
            'date'     => __( 'Date', 'bookly-cart' ),
            'time'     => __( 'Time', 'bookly-cart' ),
            'employee' => BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_employee' ),
            'price'    => __( 'Price', 'bookly-cart' ),
            'deposit'  => __( 'Deposit', 'bookly-cart' ),
            'tax'      => __( 'Tax', 'bookly-cart' ),
        );

        $this->render( 'settings_form', compact( 'cart_columns' ) );
    }

}