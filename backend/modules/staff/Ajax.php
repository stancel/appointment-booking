<?php
namespace Bookly\Backend\Modules\Staff;

use Bookly\Lib;
use Bookly\Backend\Modules\Staff\Forms\Widgets\TimeChoice;

/**
 * Class Page
 * @package Bookly\Backend\Modules\Staff
 */
class Ajax extends Lib\Base\Ajax
{
    /**
     * @inheritdoc
     */
    protected static function permissions()
    {
        $permissions = get_option( 'bookly_gen_allow_staff_edit_profile' ) ? array( '_default' => 'user' ) : array();
        if ( Lib\Config::staffCabinetEnabled() ) {
            $permissions = array( '_default' => 'user' );
        }

        return $permissions;
    }

    /**
     * Create staff.
     */
    public static function createStaff()
    {
        $form = new Forms\StaffMemberNew();
        $form->bind( self::postParameters() );

        $staff = $form->save();
        if ( $staff ) {
            wp_send_json_success( array( 'html' => self::renderTemplate( '_list_item', array( 'staff' => $staff->getFields() ), false ) ) );
        }
    }

    /**
     * Update staff position.
     */
    public static function updateStaffPosition()
    {
        $staff_sorts = self::parameter( 'position' );
        foreach ( $staff_sorts as $position => $staff_id ) {
            $staff_sort = new Lib\Entities\Staff();
            $staff_sort->load( $staff_id );
            $staff_sort->setPosition( $position );
            $staff_sort->save();
        }
    }

    /**
     * Get staff services.
     */
    public static function getStaffServices()
    {
        $form        = new Forms\StaffServices();
        $staff_id    = self::parameter( 'staff_id' );
        $location_id = self::parameter( 'location_id' );

        $form->load( $staff_id, $location_id );
        $services_data = $form->getServicesData();

        $html = self::renderTemplate( 'services', compact( 'form', 'services_data', 'staff_id', 'location_id' ), false );
        wp_send_json_success( compact( 'html' ) );
    }

    /**
     * Get staff schedule.
     */
    public static function getStaffSchedule()
    {
        $staff_id = self::parameter( 'staff_id' );
        $staff    = new Lib\Entities\Staff();
        $staff->load( $staff_id );
        $schedule_items = $staff->getScheduleItems();
        $html = self::renderTemplate( 'schedule', compact( 'schedule_items', 'staff_id' ), false );
        wp_send_json_success( compact( 'html' ) );
    }

    /**
     * Update staff schedule.
     */
    public static function staffScheduleUpdate()
    {
        $form = new Forms\StaffSchedule();
        $form->bind( self::postParameters() );
        $form->save();
        wp_send_json_success();
    }

    /**
     * Reset breaks.
     */
    public static function resetBreaks()
    {
        $breaks = self::parameter( 'breaks' );

        if ( ! Lib\Utils\Common::isCurrentUserAdmin() ) {
            // Check permissions to prevent one staff member from updating profile of another staff member.
            do {
                if ( self::parameter( 'staff_cabinet' ) && Lib\Config::staffCabinetEnabled() ) {
                    $allow = true;
                } else {
                    $allow = get_option( 'bookly_gen_allow_staff_edit_profile' );
                }
                if ( $allow ) {
                    $breaks = self::parameter( 'breaks' );
                    $staff = new Lib\Entities\Staff();
                    $staff->load( $breaks['staff_id'] );
                    if ( $staff->getWpUserId() == get_current_user_id() ) {
                        break;
                    }
                }
                do_action( 'admin_page_access_denied' );
                wp_die( 'Bookly: ' . __( 'You do not have sufficient permissions to access this page.' ) );
            } while ( 0 );
        }

        $html_breaks = array();

        // Remove all breaks for staff member.
        $break = new Lib\Entities\ScheduleItemBreak();
        $break->removeBreaksByStaffId( $breaks['staff_id'] );

        // Restore previous breaks.
        if ( isset( $breaks['breaks'] ) && is_array( $breaks['breaks'] ) ) {
            foreach ( $breaks['breaks'] as $day ) {
                $schedule_item_break = new Lib\Entities\ScheduleItemBreak();
                $schedule_item_break->setFields( $day );
                $schedule_item_break->save();
            }
        }

        $staff = new Lib\Entities\Staff();
        $staff->load( $breaks['staff_id'] );

        // Make array with breaks (html) for each day.
        foreach ( $staff->getScheduleItems() as $item ) {
            /** @var Lib\Entities\StaffScheduleItem $item */
            $html_breaks[ $item->getId() ] = self::renderTemplate( '_breaks', array(
                'day_is_not_available' => null === $item->getStartTime(),
                'item'                 => $item,
                'break_start'          => new TimeChoice( array( 'use_empty' => false, 'type' => 'break_from' ) ),
                'break_end'            => new TimeChoice( array( 'use_empty' => false, 'type' => 'to' ) ),
            ), false );
        }

        wp_send_json( $html_breaks );
    }

    /**
     * Handle staff schedule break.
     */
    public static function staffScheduleHandleBreak()
    {
        $start_time    = self::parameter( 'start_time' );
        $end_time      = self::parameter( 'end_time' );
        $working_start = self::parameter( 'working_start' );
        $working_end   = self::parameter( 'working_end' );

        if ( Lib\Utils\DateTime::timeToSeconds( $start_time ) >= Lib\Utils\DateTime::timeToSeconds( $end_time ) ) {
            wp_send_json_error( array( 'message' => __( 'The start time must be less than the end one', 'bookly' ), ) );
        }

        $res_schedule = new Lib\Entities\StaffScheduleItem();
        $res_schedule->load( self::parameter( 'staff_schedule_item_id' ) );

        $break_id = self::parameter( 'break_id', 0 );

        $in_working_time = $working_start <= $start_time && $start_time <= $working_end
            && $working_start <= $end_time && $end_time <= $working_end;
        if ( ! $in_working_time || ! $res_schedule->isBreakIntervalAvailable( $start_time, $end_time, $break_id ) ) {
            wp_send_json_error( array( 'message' => __( 'The requested interval is not available', 'bookly' ), ) );
        }

        $formatted_start    = Lib\Utils\DateTime::formatTime( Lib\Utils\DateTime::timeToSeconds( $start_time ) );
        $formatted_end      = Lib\Utils\DateTime::formatTime( Lib\Utils\DateTime::timeToSeconds( $end_time ) );
        $formatted_interval = $formatted_start . ' - ' . $formatted_end;

        if ( $break_id ) {
            $break = new Lib\Entities\ScheduleItemBreak();
            $break->load( $break_id );
            $break->setStartTime( $start_time )
                ->setEndTime( $end_time )
                ->save();

            wp_send_json_success( array( 'interval' => $formatted_interval, ) );
        } else {
            $form = new Forms\StaffScheduleItemBreak();
            $form->bind( self::postParameters() );

            $res_schedule_break = $form->save();
            if ( $res_schedule_break ) {
                $breakStart = new TimeChoice( array( 'use_empty' => false, 'type' => 'break_from' ) );
                $breakEnd   = new TimeChoice( array( 'use_empty' => false, 'type' => 'to' ) );
                wp_send_json( array(
                    'success'      => true,
                    'item_content' => self::renderTemplate( '_break', array(
                        'staff_schedule_item_break_id' => $res_schedule_break->getId(),
                        'formatted_interval'           => $formatted_interval,
                        'break_start_choices'          => $breakStart->render( '', $start_time, array( 'class' => 'break-start form-control' ) ),
                        'break_end_choices'            => $breakEnd->render( '', $end_time, array( 'class' => 'break-end form-control' ) ),
                    ), false ),
                ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'Error adding the break interval', 'bookly' ), ) );
            }
        }
    }

    /**
     * Delete staff schedule break.
     */
    public static function deleteStaffScheduleBreak()
    {
        $break = new Lib\Entities\ScheduleItemBreak();
        $break->setId( self::parameter( 'id', 0 ) );
        $break->delete();

        wp_send_json_success();
    }

    /**
     * Update staff services.
     */
    public static function staffServicesUpdate()
    {
        $form = new Forms\StaffServices();
        $form->bind( self::postParameters() );
        $form->save();

        Proxy\Shared::updateStaffServices( self::postParameters() );

        wp_send_json_success();
    }

    /**
     * Edit staff.
     */
    public static function editStaff()
    {
        $alert = array( 'error' => array() );
        $form  = new Forms\StaffMember();
        $staff = new Lib\Entities\Staff();
        $staff->load( self::parameter( 'id' ) );

        if ( $gc_errors = Lib\Session::get( 'staff_google_auth_error' ) ) {
            foreach ( (array) json_decode( $gc_errors, true ) as $error ) {
                $alert['error'][] = $error;
            }
            Lib\Session::destroy( 'staff_google_auth_error' );
        }

        $google_calendars   = array();
        $google_calendar_id = null;
        $auth_url           = null;
        if ( $staff->getGoogleData() == '' ) {
            if ( Lib\Config::getGoogleCalendarSyncMode() !== false ) {
                $google  = new Lib\Google\Client();
                $auth_url = $google->createAuthUrl( self::parameter( 'id' ) );
            } else {
                $auth_url = false;
            }
        } else {
            $google = new Lib\Google\Client();
            if ( $google->auth( $staff ) && ( $list = $google->getCalendarList() ) !== false ) {
                $google_calendars   = $list;
                $google_calendar_id = $google->data()->calendar->id;
            } else {
                foreach ( $google->getErrors() as $error ) {
                    $alert['error'][] = $error;
                }
            }
        }

        $users_for_staff = Lib\Utils\Common::isCurrentUserAdmin() ? $form->getUsersForStaff( $staff->getId() ) : array();

        wp_send_json_success( array(
            'html'  => array(
                'edit'    => self::renderTemplate( 'edit', compact( 'staff' ), false ),
                'details' => self::renderTemplate(
                    '_details',
                    compact( 'staff', 'auth_url', 'google_calendars', 'google_calendar_id', 'users_for_staff' ),
                    false
                ),
            ),
            'alert' => $alert,
        ) );
    }

    /**
     * Update staff from POST request.
     */
    public static function updateStaff()
    {
        if ( ! Lib\Utils\Common::isCurrentUserAdmin() ) {
            // Check permissions to prevent one staff member from updating profile of another staff member.
            do {
                if ( self::parameter( 'staff_cabinet' ) && Lib\Config::staffCabinetEnabled() ) {
                    $allow = true;
                } else {
                    $allow = get_option( 'bookly_gen_allow_staff_edit_profile' );
                }
                if ( $allow ) {
                    $staff = Lib\Entities\Staff::find( self::parameter( 'id' ) );
                    if ( $staff->getWpUserId() == get_current_user_id() ) {
                        unset ( $_POST['wp_user_id'] );
                        break;
                    }
                }
                do_action( 'admin_page_access_denied' );
                wp_die( 'Bookly: ' . __( 'You do not have sufficient permissions to access this page.' ) );
            } while ( 0 );
        }

        $post = self::postParameters();

        // Handle Google Calendar.
        if ( isset ( $post['google_calendar_id'] ) ) {
            $calendar_id = $post['google_calendar_id'];
            $staff       = Lib\Entities\Staff::find( self::parameter( 'id' ) );
            $google      = new Lib\Google\Client();
            if ( $google->auth( $staff ) && $calendar_id != $google->data()->calendar->id ) {
                if ( $calendar_id != '' ) {
                    if ( ! $google->validateCalendarId( $calendar_id ) ) {
                        wp_send_json_error( array( 'error' => implode( '<br>', $google->getErrors() ) ) );
                    }
                } else {
                    $calendar_id = null;
                }
                if ( Lib\Config::advancedGoogleCalendarActive() ) {
                    $google->calendar()->stopWatching( false );
                }
                $google_data = $google->data();
                $google_data->calendar->id = $calendar_id;
                $google_data->calendar->sync_token = null;
                $post['google_data'] = $google_data->toJson();
            }
        }

        $form = new Forms\StaffMemberEdit();
        $form->bind( $post, $_FILES );
        $form->save();

        Proxy\Shared::updateStaff( $post );

        $wp_users = array();
        if ( Lib\Utils\Common::isCurrentUserAdmin() ) {
            $form     = new Forms\StaffMember();
            $wp_users = $form->getUsersForStaff();
        }

        wp_send_json_success( compact( 'wp_users' ) );
    }

    /**
     * 'Safely' remove staff (report if there are future appointments)
     */
    public static function deleteStaff()
    {
        $wp_users = array();

        if ( Lib\Utils\Common::isCurrentUserAdmin() ) {
            $staff_id = self::parameter( 'id' );

            if ( self::parameter( 'force_delete', false ) ) {
                if ( $staff = Lib\Entities\Staff::find( $staff_id ) ) {
                    $staff->delete();
                }

                $form = new Forms\StaffMember();
                $wp_users = $form->getUsersForStaff();
            } else {
                /** @var Lib\Entities\Appointment $appointment */
                $appointment = Lib\Entities\Appointment::query( 'a' )
                    ->select( 'MAX(a.start_date) AS start_date')
                    ->where( 'a.staff_id', $staff_id )
                    ->whereGt( 'a.start_date', current_time( 'mysql' ) )
                    ->groupBy( 'a.staff_id' )
                    ->findOne();

                if ( $appointment ) {
                    $last_month = date_create( $appointment->getStartDate() )->modify( 'last day of' )->format( 'Y-m-d' );
                    $action = 'show_modal';
                    $filter_url = sprintf( '%s#staff=%d&range=%s-%s',
                        Lib\Utils\Common::escAdminUrl( \Bookly\Backend\Modules\Appointments\Ajax::pageSlug() ),
                        $staff_id,
                        date_create( current_time( 'mysql' ) )->format( 'Y-m-d' ),
                        $last_month );
                    wp_send_json_error( compact( 'action', 'filter_url' ) );
                } else {
                    $action = 'confirm';
                    wp_send_json_error( compact( 'action' ) );
                }
            }
        }

        wp_send_json_success( compact( 'wp_users' ) );
    }

    /**
     * Delete staff avatar.
     */
    public static function deleteStaffAvatar()
    {
        $staff = new Lib\Entities\Staff();
        $staff->load( self::parameter( 'id' ) );
        $staff->setAttachmentId( null );
        $staff->save();

        wp_send_json_success();
    }

    /**
     * Get staff holidays.
     */
    public static function staffHolidays()
    {
        /** @var \WP_Locale $wp_locale */
        global $wp_locale;

        $staff_id           = self::parameter( 'id', 0 );
        $holidays           = self::_getHolidays( $staff_id );
        $loading_img        = plugins_url( 'appointment-booking/backend/resources/images/loading.gif' );
        $start_of_week      = (int) get_option( 'start_of_week' );
        $days               = array_values( $wp_locale->weekday_abbrev );
        $months             = array_values( $wp_locale->month );
        $close              = __( 'Close', 'bookly' );
        $repeat             = __( 'Repeat every year', 'bookly' );
        $we_are_not_working = __( 'We are not working on this day', 'bookly' );
        $html               = self::renderTemplate( 'holidays', array(), false );
        wp_send_json_success( compact( 'html', 'holidays', 'days', 'months', 'start_of_week', 'loading_img', 'we_are_not_working', 'repeat', 'close' ) );
    }

    /**
     * Update staff holidays.
     */
    public static function staffHolidaysUpdate()
    {
        global $wpdb;

        $id       = self::parameter( 'id' );
        $holiday  = self::parameter( 'holiday' ) == 'true';
        $repeat   = self::parameter( 'repeat' ) == 'true';
        $day      = self::parameter( 'day', false );
        $staff_id = self::parameter( 'staff_id' );
        if ( $staff_id ) {
            // Update or delete the event.
            if ( $id ) {
                if ( $holiday ) {
                    $wpdb->update( Lib\Entities\Holiday::getTableName(), array( 'repeat_event' => (int) $repeat ), array( 'id' => $id ), array( '%d' ) );
                } else {
                    Lib\Entities\Holiday::query()->delete()->where( 'id', $id )->execute();
                }
                // Add the new event.
            } elseif ( $holiday && $day ) {
                $wpdb->insert( Lib\Entities\Holiday::getTableName(), array( 'date' => $day, 'repeat_event' => (int) $repeat, 'staff_id' => $staff_id ), array( '%s', '%d', '%d' ) );
            }
            // And return refreshed events.
            echo json_encode( self::_getHolidays( $staff_id ) );
        }
        exit;
    }

    /**
     * Get holidays.
     *
     * @param int $staff_id
     * @return array
     */
    private static function _getHolidays( $staff_id )
    {
        $collection = Lib\Entities\Holiday::query( 'h' )->where( 'h.staff_id', $staff_id )->fetchArray();
        $holidays = array();
        foreach ( $collection as $holiday ) {
            list ( $Y, $m, $d ) = explode( '-', $holiday['date'] );
            $holidays[ $holiday['id'] ] = array(
                'm' => (int) $m,
                'd' => (int) $d,
            );
            // if not repeated holiday, add the year
            if ( ! $holiday['repeat_event'] ) {
                $holidays[ $holiday['id'] ]['y'] = (int) $Y;
            }
        }

        return $holidays;
    }

    /**
     * Extend parent method to control access on staff member level.
     *
     * @param string $action
     * @return bool
     */
    protected static function hasAccess( $action )
    {
        if ( parent::hasAccess( $action ) ) {
            if ( ! Lib\Utils\Common::isCurrentUserAdmin() ) {
                $staff = new Lib\Entities\Staff();

                switch ( $action ) {
                    case 'editStaff':
                    case 'deleteStaffAvatar':
                    case 'staffSchedule':
                    case 'staffHolidays':
                    case 'updateStaff':
                    case 'getStaffDetails':
                        $staff->load( self::parameter( 'id' ) );
                        break;
                    case 'getStaffServices':
                    case 'getStaffSchedule':
                    case 'staffServicesUpdate':
                    case 'staffHolidaysUpdate':
                        $staff->load( self::parameter( 'staff_id' ) );
                        break;
                    case 'staffScheduleHandleBreak':
                        $res_schedule = new Lib\Entities\StaffScheduleItem();
                        $res_schedule->load( self::parameter( 'staff_schedule_item_id' ) );
                        $staff->load( $res_schedule->getStaffId() );
                        break;
                    case 'deleteStaffScheduleBreak':
                        $break = new Lib\Entities\ScheduleItemBreak();
                        $break->load( self::parameter( 'id' ) );
                        $res_schedule = new Lib\Entities\StaffScheduleItem();
                        $res_schedule->load( $break->getStaffScheduleItemId() );
                        $staff->load( $res_schedule->getStaffId() );
                        break;
                    case 'staffScheduleUpdate':
                        if ( self::hasParameter( 'days' ) ) {
                            foreach ( self::parameter( 'days' ) as $id => $day_index ) {
                                $res_schedule = new Lib\Entities\StaffScheduleItem();
                                $res_schedule->load( $id );
                                $staff = new Lib\Entities\Staff();
                                $staff->load( $res_schedule->getStaffId() );
                                if ( $staff->getWpUserId() != get_current_user_id() ) {
                                    return false;
                                }
                            }
                        }
                        break;
                    case 'resetBreaks':
                        $parameter = self::parameter( 'breaks' );
                        if ( $parameter && isset( $parameter['staff_id'] ) ) {
                            $staff->load( $parameter['staff_id'] );
                        }
                        break;
                    default:
                        return false;
                }

                return $staff->getWpUserId() == get_current_user_id();
            }

            return true;
        }

        return false;
    }
}