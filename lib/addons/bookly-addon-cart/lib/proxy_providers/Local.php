<?php
namespace BooklyCart\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyCart\Lib;
use BooklyCart\Backend\Modules as Backend;
use BooklyCart\Frontend\Modules as Frontend;

/**
 * Class Local
 * Provides local methods to be used in Bookly and other add-ons.
 *
 * @package BooklyCart\Lib\ProxyProviders
 */
abstract class Local extends BooklyLib\Base\ProxyProvider
{
    /**
     * Render cart step in appearance.
     *
     * @param string $progress_tracker
     */
    public static function renderAppearance( $progress_tracker )
    {
        if ( Lib\Plugin::enabled() ) {
            Backend\Appearance\Controller::getInstance()->renderAppearance( $progress_tracker );
        }
    }

    /**
     * Render cart step on frontend.
     *
     * @param BooklyLib\UserBookingData $userData
     * @param string $progress_tracker
     * @param string $info_text
     * @return string
     */
    public static function getStepHtml( BooklyLib\UserBookingData $userData, $progress_tracker, $info_text )
    {
        return Frontend\Booking\Controller::getInstance()->renderStep( $userData, $progress_tracker, $info_text );
    }
}