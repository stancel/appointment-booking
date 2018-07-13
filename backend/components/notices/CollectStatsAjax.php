<?php
namespace Bookly\Backend\Components\Notices;

use Bookly\Lib;

/**
 * Class CollectStatsAjax
 * @package Bookly\Backend\Components\Notices
 */
class CollectStatsAjax extends Lib\Base\Ajax
{
    /**
     * Dismiss collect stats notice.
     */
    public static function dismissCollectStatsNotice()
    {
        update_user_meta( get_current_user_id(), Lib\Plugin::getPrefix() . 'dismiss_collect_stats_notice', 1 );

        wp_send_json_success();
    }
}