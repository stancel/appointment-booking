<?php
namespace Bookly\Backend\Components\Notices;

use Bookly\Lib;

/**
 * Class CollectStats
 * @package Bookly\Backend\Components\Notices
 */
class CollectStats extends Lib\Base\Component
{
    /**
     * Render collect stats notice.
     */
    public static function render()
    {
        if ( Lib\Utils\Common::isCurrentUserAdmin() &&
            get_option( 'bookly_gen_collect_stats' ) == '1' &&
            ! get_user_meta( get_current_user_id(), Lib\Plugin::getPrefix() . 'dismiss_collect_stats_notice', true )
        ) {
            self::enqueueStyles( array(
                'frontend' => array( 'css/ladda.min.css', ),
            ) );
            self::enqueueScripts( array(
                'module'  => array( 'js/collect-stats.js' => array( 'jquery' ), ),
            ) );

            self::renderTemplate( 'collect_stats' );
        }
    }
}