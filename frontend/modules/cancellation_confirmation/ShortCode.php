<?php
namespace Bookly\Frontend\Modules\CancellationConfirmation;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\CancellationConfirmation
 */
class ShortCode extends Lib\Base\Component
{
    /**
     * Render shortcode.
     *
     * @param array $attributes
     * @return string
     */
    public static function generate( $attributes )
    {
        // Disable caching.
        Lib\Utils\Common::noCache();

        // Prepare URL for AJAX requests.
        $ajax_url = admin_url( 'admin-ajax.php' );

        $token = self::parameter( 'bookly-appointment-token', '' );

        return self::renderTemplate( 'short_code', compact( 'ajax_url', 'token' ), false );
    }
}