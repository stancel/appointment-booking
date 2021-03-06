<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Lib\Utils\Common;
use Bookly\Backend\Components;
use Bookly\Backend\Modules as Backend;
use Bookly\Backend\Modules\Calendar\Proxy;
/** @var Bookly\Lib\Entities\Staff[] $staff_members */
?>
<style>
    .fc-slats tr { height: <?php echo max( 21, (int) ( 0.43 * get_option( 'bookly_gen_time_slot_length' ) ) ) ?>px; }
    .fc-time-grid-event.fc-short .fc-time::after { content: '' !important; }
</style>
<div id="bookly-tbs" class="wrap">
    <div class="bookly-tbs-body">
        <div class="page-header text-right clearfix">
            <div class="bookly-page-title">
                <?php _e( 'Calendar', 'bookly' ) ?>
            </div>
            <?php if ( Common::isCurrentUserAdmin() ) : ?>
                <?php Components\Support\Buttons::render( $self::pageSlug() ) ?>
            <?php endif ?>
        </div>
        <div class="panel panel-default bookly-main bookly-fc-inner">
            <div class="panel-body">
                <?php if ( $staff_members ) : ?>
                <ul class="bookly-nav bookly-nav-tabs">
                    <?php if ( Common::isCurrentUserAdmin() ) : ?>
                        <li class="bookly-nav-item bookly-js-calendar-tab" data-staff_id="0">
                            <?php _e( 'All', 'bookly' ) ?>
                        </li>
                    <?php endif ?>
                    <?php foreach ( $staff_members as $staff ) : ?>
                        <li class="bookly-nav-item bookly-js-calendar-tab" data-staff_id="<?php echo $staff->getId() ?>" style="display: none">
                            <?php echo $staff->getFullName() ?>
                        </li>
                    <?php endforeach ?>
                    <?php if ( Common::isCurrentUserAdmin() ) : ?>
                        <div class="btn-group pull-right bookly-margin-top-xs">
                            <button class="btn btn-default dropdown-toggle bookly-js-staff-filter bookly-flexbox" data-toggle="dropdown">
                                <div class="bookly-flex-cell"><i class="dashicons dashicons-admin-users bookly-margin-right-md"></i></div>
                                <div class="bookly-flex-cell text-left"><span id="bookly-staff-button"></span></div>
                                <div class="bookly-flex-cell"><div class="bookly-margin-left-md"><span class="caret"></span></div></div>
                            </button>
                            <ul class="dropdown-menu bookly-entity-selector">
                                <li>
                                    <a class="checkbox" href="javascript:void(0)">
                                        <label><input type="checkbox" id="bookly-check-all-entities"><?php _e( 'All staff', 'bookly' ) ?></label>
                                    </a>
                                </li>
                                <?php foreach ( $staff_members as $staff ) : ?>
                                    <li>
                                        <a class="checkbox" href="javascript:void(0)">
                                            <label>
                                                <input type="checkbox" id="bookly-filter-staff-<?php echo $staff->getId() ?>" value="<?php echo $staff->getId() ?>" data-staff_name="<?php echo esc_attr( $staff->getFullName() ) ?>" class="bookly-js-check-entity">
                                                <?php echo $staff->getFullName() ?>
                                            </label>
                                        </a>
                                    </li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    <?php endif ?>
                    <?php Proxy\Locations::renderCalendarLocationFilter() ?>
                    <?php Proxy\AdvancedGoogleCalendar::renderSyncButton( $staff_members ) ?>
                </ul>
                <?php endif ?>
                <div class="bookly-margin-top-xlg">
                    <?php if ( $staff_members ) : ?>
                        <div class="fc-loading-inner" style="display: none">
                            <div class="fc-loading"></div>
                        </div>
                        <div id="bookly-fc-wrapper" class="bookly-calendar">
                            <div class="bookly-js-calendar-element"></div>
                        </div>
                        <?php Components\Dialogs\Appointment\Edit\Dialog::render() ?>
                        <?php Proxy\Shared::renderAddOnsComponents() ?>
                    <?php else : ?>
                        <div class="well">
                            <div class="h1"><?php _e( 'Welcome to Bookly!', 'bookly' ) ?></div>
                            <h3><?php _e( 'Thank you for purchasing our product.', 'bookly' ) ?></h3>
                            <h3><?php _e( 'Bookly offers a simple solution for making appointments. With our plugin you will be able to easily manage your availability time and handle the flow of your clients.', 'bookly' ) ?></h3>
                            <p><?php _e( 'To start using Bookly, you need to follow these steps which are the minimum requirements to get it running!', 'bookly' ) ?></p>
                            <ol>
                                <li><?php _e( 'Add staff members.', 'bookly' ) ?></li>
                                <li><?php _e( 'Add services and assign them to staff members.', 'bookly' ) ?></li>
                            </ol>
                            <hr>
                            <a class="btn btn-success" href="<?php echo Common::escAdminUrl( Backend\Staff\Ajax::pageSlug() ) ?>">
                                <?php _e( 'Add Staff Members', 'bookly' ) ?>
                            </a>
                            <a class="btn btn-success" href="<?php echo Common::escAdminUrl( Backend\Services\Ajax::pageSlug() ) ?>">
                                <?php _e( 'Add Services', 'bookly' ) ?>
                            </a>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <?php Components\Dialogs\Appointment\Delete\Dialog::render() ?>
    </div>
</div>