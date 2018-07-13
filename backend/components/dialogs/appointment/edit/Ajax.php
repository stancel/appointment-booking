<?php
namespace Bookly\Backend\Components\Dialogs\Appointment\Edit;

use Bookly\Lib;
use Bookly\Lib\DataHolders\Booking as DataHolders;
use Bookly\Backend\Modules\Calendar;

/**
 * Class Ajax
 * @package Bookly\Backend\Components\Dialogs\Appointment\Edit
 */
class Ajax extends Lib\Base\Ajax
{
    /**
     * @inheritdoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'user' );
    }

    /**
     * Get data needed for appointment form initialisation.
     */
    public static function getDataForAppointmentForm()
    {
        $type = self::parameter( 'type', false ) == 'package'
            ? Lib\Entities\Service::TYPE_PACKAGE
            : Lib\Entities\Service::TYPE_SIMPLE;

        $result = array(
            'staff'          => array(),
            'customers'      => array(),
            'start_time'     => array(),
            'end_time'       => array(),
            'app_start_time' => null,  // Appointment start time which may not be in the list of start times.
            'app_end_time'   => null,  // Appointment end time which may not be in the list of end times.
            'week_days'      => array(),
            'time_interval'  => Lib\Config::getTimeSlotLength(),
            'status'         => array(
                'items' => array(
                    'pending'    => Lib\Entities\CustomerAppointment::statusToString( Lib\Entities\CustomerAppointment::STATUS_PENDING ),
                    'approved'   => Lib\Entities\CustomerAppointment::statusToString( Lib\Entities\CustomerAppointment::STATUS_APPROVED ),
                    'cancelled'  => Lib\Entities\CustomerAppointment::statusToString( Lib\Entities\CustomerAppointment::STATUS_CANCELLED ),
                    'rejected'   => Lib\Entities\CustomerAppointment::statusToString( Lib\Entities\CustomerAppointment::STATUS_REJECTED ),
                    'waitlisted' => Lib\Entities\CustomerAppointment::statusToString( Lib\Entities\CustomerAppointment::STATUS_WAITLISTED ),
                )
            ),
        );

        // Staff list.
        $staff_members = Lib\Utils\Common::isCurrentUserAdmin()
            ? Lib\Entities\Staff::query()->sortBy( 'position' )->find()
            : Lib\Entities\Staff::query()->where( 'wp_user_id', get_current_user_id() )->find();

        $custom_service_max_capacity = Lib\Config::groupBookingActive() ? 9999 : 1;
        /** @var Lib\Entities\Staff $staff_member */
        foreach ( $staff_members as $staff_member ) {
            $services = array();
            if ( $type == Lib\Entities\Service::TYPE_SIMPLE ) {
                $services[] = array(
                    'id'        => null,
                    'title'     => __( 'Custom', 'bookly' ),
                    'duration'  => Lib\Config::getTimeSlotLength(),
                    'locations' => array(
                        0 => array(
                            'capacity_min' => 1,
                            'capacity_max' => $custom_service_max_capacity,
                        ),
                    ),
                );
            }
            foreach ( $staff_member->getStaffServices( $type ) as $staff_service ) {
                $sub_services = $staff_service->service->getSubServices();
                if ( $type == Lib\Entities\Service::TYPE_SIMPLE || ! empty( $sub_services ) ) {
                    if ( $staff_service->getLocationId() === null || Lib\Proxy\Locations::prepareStaffLocationId( $staff_service->getLocationId(), $staff_service->getStaffId() ) == $staff_service->getLocationId() ) {
                        if ( ! in_array( $staff_service->service->getId(), array_map( function ( $service ) { return $service['id']; }, $services ) ) ) {
                            $services[] = array(
                                'id'        => $staff_service->service->getId(),
                                'title'     => sprintf(
                                    '%s (%s)',
                                    $staff_service->service->getTitle(),
                                    Lib\Utils\DateTime::secondsToInterval( $staff_service->service->getDuration() )
                                ),
                                'duration'  => $staff_service->service->getDuration(),
                                'locations' => array(
                                    ( $staff_service->getLocationId() ?: 0 ) => array(
                                        'capacity_min' => Lib\Config::groupBookingActive() ? $staff_service->getCapacityMin() : 1,
                                        'capacity_max' => Lib\Config::groupBookingActive() ? $staff_service->getCapacityMax() : 1,
                                    ),
                                ),
                            );
                        } else {
                            array_walk( $services, function ( &$item ) use ( $staff_service ) {
                                if ( $item['id'] == $staff_service->service->getId() ) {
                                    $item['locations'][ $staff_service->getLocationId() ?: 0 ] = array(
                                        'capacity_min' => Lib\Config::groupBookingActive() ? $staff_service->getCapacityMin() : 1,
                                        'capacity_max' => Lib\Config::groupBookingActive() ? $staff_service->getCapacityMax() : 1,
                                    );
                                }
                            } );
                        }
                    }
                }
            }
            $locations = array();
            foreach ( (array) Lib\Proxy\Locations::findByStaffId( $staff_member->getId() ) as $location ) {
                $locations[] = array(
                    'id'   => $location->getId(),
                    'name' => $location->getName(),
                );
            }
            $result['staff'][] = array(
                'id'        => $staff_member->getId(),
                'full_name' => $staff_member->getFullName(),
                'services'  => $services,
                'locations' => $locations,
            );
        }

        /** @var Lib\Entities\Customer $customer */
        // Customers list.
        foreach ( Lib\Entities\Customer::query()->sortBy( 'full_name' )->find() as $customer ) {
            $name = $customer->getFullName();
            if ( $customer->getEmail() != '' || $customer->getPhone() != '' ) {
                $name .= ' (' . trim( $customer->getEmail() . ', ' . $customer->getPhone(), ', ' ) . ')';
            }

            $result['customers'][] = array(
                'id'                 => $customer->getId(),
                'name'               => $name,
                'status'             => Lib\Proxy\CustomerGroups::prepareDefaultAppointmentStatus( get_option( 'bookly_gen_default_appointment_status' ), $customer->getGroupId(), 'backend' ),
                'custom_fields'      => array(),
                'number_of_persons'  => 1,
            );
        }

        // Time list.
        $ts_length  = Lib\Config::getTimeSlotLength();
        $time_start = 0;
        $time_end   = DAY_IN_SECONDS * 2;

        // Run the loop.
        while ( $time_start <= $time_end ) {
            $slot = array(
                'value' => Lib\Utils\DateTime::buildTimeString( $time_start, false ),
                'title' => Lib\Utils\DateTime::formatTime( $time_start ),
            );
            if ( $time_start < DAY_IN_SECONDS ) {
                $result['start_time'][] = $slot;
            }
            $result['end_time'][] = $slot;
            $time_start += $ts_length;
        }

        $days_times = Lib\Config::getDaysAndTimes();
        $weekdays  = array( 1 => 'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', );
        foreach ( $days_times['days'] as $index => $abbrev ) {
            $result['week_days'][] = $weekdays[ $index ];
        }

        if ( $type == Lib\Entities\Service::TYPE_PACKAGE ) {
            $result = Proxy\Shared::prepareDataForPackage( $result );
        }

        wp_send_json( $result );
    }

    /**
     * Get appointment data when editing an appointment.
     */
    public static function getDataForAppointment()
    {
        $response = array( 'success' => false, 'data' => array( 'customers' => array() ) );

        $appointment = new Lib\Entities\Appointment();
        if ( $appointment->load( self::parameter( 'id' ) ) ) {
            $response['success'] = true;

            $query = Lib\Entities\Appointment::query( 'a' )
                ->select( 'SUM(ca.number_of_persons) AS total_number_of_persons,
                    a.staff_id,
                    a.staff_any,
                    a.service_id,
                    a.custom_service_name,
                    a.custom_service_price,
                    a.start_date,
                    a.end_date,
                    a.internal_note,
                    a.series_id,
                    a.location_id' )
                ->leftJoin( 'CustomerAppointment', 'ca', 'ca.appointment_id = a.id' )
                ->leftJoin( 'StaffService', 'ss', 'ss.staff_id = a.staff_id AND ss.service_id = a.service_id AND ss.location_id = a.location_id' )
                ->where( 'a.id', $appointment->getId() );
            if ( Lib\Config::groupBookingActive() ) {
                $query->addSelect( 'ss.capacity_min AS min_capacity, ss.capacity_max AS max_capacity' );
            } else {
                $query->addSelect( '1 AS min_capacity, 1 AS max_capacity' );
            }

            $info = $query->fetchRow();
            $response['data']['total_number_of_persons'] = $info['total_number_of_persons'];
            $response['data']['min_capacity']            = $info['min_capacity'];
            $response['data']['max_capacity']            = $info['max_capacity'];
            $response['data']['start_date']              = $info['start_date'];
            $response['data']['end_date']                = $info['end_date'];
            $response['data']['start_time']              = array(
                'value' => date( 'H:i', strtotime( $info['start_date'] ) ),
                'title' => Lib\Utils\DateTime::formatTime( $info['start_date'] ),
            );
            $response['data']['end_time']                = array(
                'value' => date( 'H:i', strtotime( $info['end_date'] ) ),
                'title' => Lib\Utils\DateTime::formatTime( $info['end_date'] ),
            );
            $response['data']['staff_id']                = $info['staff_id'];
            $response['data']['staff_any']               = (int) $info['staff_any'];
            $response['data']['service_id']              = $info['service_id'];
            $response['data']['custom_service_name']     = $info['custom_service_name'];
            $response['data']['custom_service_price']    = (float) $info['custom_service_price'];
            $response['data']['internal_note']           = $info['internal_note'];
            $response['data']['series_id']               = $info['series_id'];
            $response['data']['location_id']             = $info['location_id'];

            $customers = Lib\Entities\CustomerAppointment::query( 'ca' )
                ->select( 'ca.id,
                    ca.customer_id,
                    ca.package_id,
                    ca.custom_fields,
                    ca.extras,
                    ca.number_of_persons,
                    ca.notes,
                    ca.status,
                    ca.payment_id,
                    ca.compound_service_id,
                    ca.compound_token,
                    p.paid    AS payment,
                    p.total   AS payment_total,
                    p.type    AS payment_type,
                    p.details AS payment_details,
                    p.status  AS payment_status' )
                ->leftJoin( 'Payment', 'p', 'p.id = ca.payment_id' )
                ->where( 'ca.appointment_id', $appointment->getId() )
                ->fetchArray();
            foreach ( $customers as $customer ) {
                $payment_title = '';
                if ( $customer['payment'] !== null ) {
                    $payment_title = Lib\Utils\Price::format( $customer['payment'] );
                    if ( $customer['payment'] != $customer['payment_total'] ) {
                        $payment_title = sprintf( __( '%s of %s', 'bookly' ), $payment_title, Lib\Utils\Price::format( $customer['payment_total'] ) );
                    }
                    $payment_title .= sprintf(
                        ' %s <span%s>%s</span>',
                        Lib\Entities\Payment::typeToString( $customer['payment_type'] ),
                        $customer['payment_status'] == Lib\Entities\Payment::STATUS_PENDING ? ' class="text-danger"' : '',
                        Lib\Entities\Payment::statusToString( $customer['payment_status'] )
                    );
                }
                $compound_service = '';
                if ( $customer['compound_service_id'] !== null ) {
                    $service = new Lib\Entities\Service();
                    if ( $service->load( $customer['compound_service_id'] ) ) {
                        $compound_service = $service->getTranslatedTitle();
                    }
                }
                $custom_fields = (array) json_decode( $customer['custom_fields'], true );
                $response['data']['customers'][] = array(
                    'id'                => $customer['customer_id'],
                    'ca_id'             => $customer['id'],
                    'package_id'        => $customer['package_id'],
                    'compound_service'  => $compound_service,
                    'compound_token'    => $customer['compound_token'],
                    'custom_fields'     => $custom_fields,
                    'files'             => Lib\Proxy\Files::getFileNamesForCustomFields( $custom_fields ),
                    'extras'            => (array) json_decode( $customer['extras'], true ),
                    'number_of_persons' => $customer['number_of_persons'],
                    'notes'             => $customer['notes'],
                    'payment_id'        => $customer['payment_id'],
                    'payment_type'      => $customer['payment'] != $customer['payment_total'] ? 'partial' : 'full',
                    'payment_title'     => $payment_title,
                    'status'            => $customer['status'],
                );
            }
        }

        wp_send_json( $response );
    }

    /**
     * Save appointment form (for both create and edit).
     */
    public static function saveAppointmentForm()
    {
        $response = array( 'success' => false );

        $appointment_id       = (int) self::parameter( 'id', 0 );
        $staff_id             = (int) self::parameter( 'staff_id', 0 );
        $service_id           = (int) self::parameter( 'service_id', -1 );
        $custom_service_name  = trim( self::parameter( 'custom_service_name' ) );
        $custom_service_price = trim( self::parameter( 'custom_service_price' ) );
        $location_id          = (int) self::parameter( 'location_id', 0 );
        $start_date           = self::parameter( 'start_date' );
        $end_date             = self::parameter( 'end_date' );
        $repeat               = json_decode( self::parameter( 'repeat', '[]' ), true );
        $schedule             = self::parameter( 'schedule', array() );
        $customers            = json_decode( self::parameter( 'customers', '[]' ), true );
        $notification         = self::parameter( 'notification', 'no' );
        $internal_note        = self::parameter( 'internal_note' );
        $created_from         = self::parameter( 'created_from' );

        if ( ! $service_id ) {
            // Custom service.
            $service_id = null;
        }
        if ( $service_id || $custom_service_name == '' ) {
            $custom_service_name = null;
        }
        if ( $service_id || $custom_service_price == '' ) {
            $custom_service_price = null;
        }
        if ( ! $location_id ) {
            $location_id = null;
        }

        // Check for errors.
        if ( ! $start_date ) {
            $response['errors']['time_interval'] = __( 'Start time must not be empty', 'bookly' );
        } elseif ( ! $end_date ) {
            $response['errors']['time_interval'] = __( 'End time must not be empty', 'bookly' );
        } elseif ( $start_date == $end_date ) {
            $response['errors']['time_interval'] = __( 'End time must not be equal to start time', 'bookly' );
        }
        if ( $service_id == -1 ) {
            $response['errors']['service_required'] = true;
        } else if ( $service_id === null && $custom_service_name === null ) {
            $response['errors']['custom_service_name_required'] = true;
        }
        $total_number_of_persons = 0;
        $max_extras_duration = 0;
        foreach ( $customers as $i => $customer ) {
            if ( $customer['status'] == Lib\Entities\CustomerAppointment::STATUS_PENDING ||
                $customer['status'] == Lib\Entities\CustomerAppointment::STATUS_APPROVED
            ) {
                $total_number_of_persons += $customer['number_of_persons'];
                $extras_duration = Lib\Proxy\ServiceExtras::getTotalDuration( $customer['extras'] );
                if ( $extras_duration > $max_extras_duration ) {
                    $max_extras_duration = $extras_duration;
                }
            }
            $customers[ $i ]['created_from'] = ( $created_from == 'backend' ) ? 'backend' : 'frontend';
        }
        if ( $service_id ) {
            $staff_service = new Lib\Entities\StaffService();
            $staff_service->loadBy( array(
                'staff_id'    => $staff_id,
                'service_id'  => $service_id,
                'location_id' => $location_id ?: null,
            ) );
            if ( ! $staff_service->isLoaded() ) {
                $staff_service->loadBy( array(
                    'staff_id'    => $staff_id,
                    'service_id'  => $service_id,
                    'location_id' => null,
                ) );
            }
            if ( $total_number_of_persons > $staff_service->getCapacityMax() ) {
                $response['errors']['overflow_capacity'] = sprintf(
                    __( 'The number of customers should not be more than %d', 'bookly' ),
                    $staff_service->getCapacityMax()
                );
            }
        }

        // If no errors then try to save the appointment.
        if ( ! isset ( $response['errors'] ) ) {
            if ( $repeat['enabled'] ) {
                // Series.
                if ( ! empty ( $schedule ) ) {
                    // Create new series.
                    $series = new Lib\Entities\Series();
                    $series
                        ->setRepeat( self::parameter( 'repeat' ) )
                        ->setToken( Lib\Utils\Common::generateToken( get_class( $series ), 'token' ) )
                        ->save();

                    if ( $notification != 'no' ) {
                        // Create order per each customer to send notifications.
                        /** @var DataHolders\Order[] $orders */
                        $orders = array();
                        foreach ( $customers as $customer ) {
                            $order = DataHolders\Order::create( Lib\Entities\Customer::find( $customer['id'] ) )
                                ->addItem( 0, DataHolders\Series::create( $series ) )
                            ;
                            $orders[ $customer['id'] ] = $order;
                        }
                    }

                    if ( $service_id ) {
                        $service = Lib\Entities\Service::find( $service_id );
                    } else {
                        $service = new Lib\Entities\Service();
                        $service
                            ->setTitle( $custom_service_name )
                            ->setDuration( Lib\Slots\DatePoint::fromStr( $end_date )->diff( Lib\Slots\DatePoint::fromStr( $start_date ) ) )
                            ->setPrice( $custom_service_price )
                        ;
                    }

                    foreach ( $schedule as $slot ) {
                        $slot = json_decode( $slot );
                        $appointment = new Lib\Entities\Appointment();
                        $appointment
                            ->setSeries( $series )
                            ->setLocationId( $location_id )
                            ->setStaffId( $staff_id )
                            ->setServiceId( $service_id )
                            ->setCustomServiceName( $custom_service_name )
                            ->setCustomServicePrice( $custom_service_price )
                            ->setStartDate( $slot[0][2] )
                            ->setEndDate( Lib\Slots\DatePoint::fromStr( $slot[0][2] )->modify( $service->getDuration() )->format( 'Y-m-d H:i:s' ) )
                            ->setInternalNote( $internal_note )
                            ->setExtrasDuration( $max_extras_duration )
                        ;

                        if ( $appointment->save() !== false ) {
                            // Save customer appointments.
                            $ca_list = $appointment->saveCustomerAppointments( $customers );
                            // Google Calendar.
                            $appointment->syncGoogleCalendar();
                            // Waiting list.
                            Lib\Proxy\WaitingList::handleParticipantsChange( $appointment );

                            if ( $notification != 'no' ) {
                                foreach ( $ca_list as $ca ) {
                                    $item = DataHolders\Simple::create( $ca )
                                        ->setService( $service )
                                        ->setAppointment( $appointment )
                                    ;
                                    $orders[ $ca->getCustomerId() ]->getItem( 0 )->addItem( $item );
                                }
                            }
                        }
                    }
                    if ( $notification != 'no' ) {
                        foreach ( $orders as $order ) {
                            Lib\Proxy\RecurringAppointments::sendRecurring( $order->getItem( 0 ), $order );
                        }
                    }
                }
                $response['success'] = true;
                $response['data']    = array( 'staffId' => $staff_id );  // make FullCalendar refetch events
            } else {
                // Single appointment.
                $appointment = new Lib\Entities\Appointment();
                if ( $appointment_id ) {
                    // Edit.
                    $appointment->load( $appointment_id );
                    if ( $appointment->getStaffId() != $staff_id ) {
                        $appointment->setStaffAny( 0 );
                    }
                }
                $appointment
                    ->setLocationId( $location_id )
                    ->setStaffId( $staff_id )
                    ->setServiceId( $service_id )
                    ->setCustomServiceName( $custom_service_name )
                    ->setCustomServicePrice( $custom_service_price )
                    ->setStartDate( $start_date )
                    ->setEndDate( $end_date )
                    ->setInternalNote( $internal_note )
                    ->setExtrasDuration( $max_extras_duration )
                ;

                if ( $appointment->save() !== false ) {
                    // Save customer appointments.
                    $ca_status_changed = $appointment->saveCustomerAppointments( $customers );

                    // Google Calendar.
                    $appointment->syncGoogleCalendar();
                    // Waiting list.
                    Lib\Proxy\WaitingList::handleParticipantsChange( $appointment );

                    // Send notifications.
                    if ( $notification == 'changed_status' ) {
                        foreach ( $ca_status_changed as $ca ) {
                            Lib\NotificationSender::sendSingle( DataHolders\Simple::create( $ca )->setAppointment( $appointment ) );
                        }
                    } elseif ( $notification == 'all' ) {
                        $ca_list = $appointment->getCustomerAppointments( true );
                        foreach ( $ca_status_changed as $ca ) {
                            // The value "just_created" was initialized for the objects of this array
                            Lib\NotificationSender::sendSingle( DataHolders\Simple::create( $ca )->setAppointment( $appointment ) );
                            unset( $ca_list[ $ca->getId() ] );
                        }
                        foreach ( $ca_list as $ca ) {
                            Lib\NotificationSender::sendSingle( DataHolders\Simple::create( $ca )->setAppointment( $appointment ) );
                        }
                    }

                    $response['success'] = true;
                    $response['data']    = self::_getAppointmentForFC( $staff_id, $appointment->getId() );
                } else {
                    $response['errors'] = array( 'db' => __( 'Could not save appointment in database.', 'bookly' ) );
                }
            }
        }
        update_user_meta( get_current_user_id(), 'bookly_appointment_form_send_notifications', $notification );

        wp_send_json( $response );
    }

    /**
     * Check whether appointment settings produce errors.
     */
    public static function checkAppointmentErrors()
    {
        $start_date     = self::parameter( 'start_date' );
        $end_date       = self::parameter( 'end_date' );
        $staff_id       = (int) self::parameter( 'staff_id' );
        $service_id     = (int) self::parameter( 'service_id' );
        $appointment_id = (int) self::parameter( 'appointment_id' );
        $appointment_duration = strtotime( $end_date ) - strtotime( $start_date );
        $customers      = json_decode( self::parameter( 'customers', '[]' ), true );
        $service        = Lib\Entities\Service::find( $service_id );
        $service_duration = $service ? $service->getDuration() : 0;

        $result = array(
            'date_interval_not_available'      => false,
            'date_interval_warning'            => false,
            'interval_not_in_staff_schedule'   => false,
            'interval_not_in_service_schedule' => false,
            'customers_appointments_limit'     => array(),
        );

        $max_extras_duration = 0;
        foreach ( $customers as $customer ) {
            if ( $customer['status'] == Lib\Entities\CustomerAppointment::STATUS_PENDING ||
                $customer['status'] == Lib\Entities\CustomerAppointment::STATUS_APPROVED
            ) {
                $extras_duration = Lib\Proxy\ServiceExtras::getTotalDuration( $customer['extras'] );
                if ( $extras_duration > $max_extras_duration ) {
                    $max_extras_duration = $extras_duration;
                }
            }
        }

        $total_end_date = $end_date;
        if ( $max_extras_duration > 0 ) {
            $total_end_date = date_create( $end_date )->modify( '+' . $max_extras_duration . ' sec' )->format( 'Y-m-d H:i:s' );
        }
        if ( ! self::_dateIntervalIsAvailableForAppointment( $start_date, $total_end_date, $staff_id, $appointment_id ) ) {
            $result['date_interval_not_available'] = true;
        }

        // Check if selected interval fit into staff schedule.
        $interval_valid = true;
        if ( $staff_id && $start_date ) {
            $staff = Lib\Entities\Staff::find( $staff_id );
            if ( $service_duration >= DAY_IN_SECONDS ) {
                // For services with duration 24+ hours check holidays and days off
                for ( $day = 0; $day < $service_duration / DAY_IN_SECONDS; $day ++ ) {
                    $work_date = date_create( $start_date )->modify( sprintf( '%s days', $day ) );
                    $week_day  = $work_date->format( 'w' ) + 1;
                    // Check staff schedule for days off
                    if ( $staff->isOnHoliday( $work_date ) ||
                        ! Lib\Entities\StaffScheduleItem::query()
                            ->select( 'id' )
                            ->where( 'staff_id', $staff_id )
                            ->where( 'day_index', $week_day )
                            ->whereNot( 'start_time', null )
                            ->fetchRow()
                    ) {
                        $interval_valid = false;
                        break;
                    }
                }
            } else {
                // Check day before and current day to get night schedule from previous day.
                $interval_valid = false;
                for ( $day = 0; $day <= 1; $day ++ ) {
                    $day_start_date = date_create( $start_date )->modify( sprintf( '%s days', $day - 1 ) );
                    $day_end_date   = date_create( $end_date )->modify( sprintf( '%s days', $day - 1 ) );
                    if ( ! $staff->isOnHoliday( $day_start_date ) ) {
                        $day_start_hour = ( 1 - $day ) * 24 + $day_start_date->format( 'G' );
                        $day_end_hour   = ( 1 - $day ) * 24 + $day_end_date->format( 'G' );
                        $day_start_time = sprintf( '%02d:%02d:00', $day_start_hour, $day_start_date->format( 'i' ) );
                        $day_end_time   = sprintf( '%02d:%02d:00', $day_end_hour >= $day_start_hour ? $day_end_hour : $day_end_hour + 24, $day_end_date->format( 'i' ) );

                        $special_days = (array) Lib\Proxy\SpecialDays::getSchedule( array( $staff_id ), $day_start_date, $day_start_date );
                        if ( ! empty( $special_days ) ) {
                            // Check if interval fit into special day schedule.
                            $special_day = current( $special_days );
                            if ( ( $special_day['start_time'] <= $day_start_time ) && ( $special_day['end_time'] >= $day_end_time ) ) {
                                if ( ! ( $special_day['break_start'] && ( $special_day['break_start'] < $day_end_time ) && ( $special_day['break_end'] > $day_start_time ) ) ) {
                                    $interval_valid = true;
                                    break;
                                }
                            }
                        } else {
                            // Check if interval fit into regular staff working schedule.
                            $week_day = $day_start_date->format( 'w' ) + 1;
                            $ssi      = Lib\Entities\StaffScheduleItem::query()
                                ->select( 'id' )
                                ->where( 'staff_id', $staff_id )
                                ->where( 'day_index', $week_day )
                                ->whereNot( 'start_time', null )
                                ->whereLte( 'start_time', $day_start_time )
                                ->whereGte( 'end_time', $day_end_time )
                                ->fetchRow();
                            if ( $ssi ) {
                                // Check if interval not intercept with breaks.
                                if ( Lib\Entities\ScheduleItemBreak::query()
                                        ->where( 'staff_schedule_item_id', $ssi['id'] )
                                        ->whereLt( 'start_time', $day_end_time )
                                        ->whereGt( 'end_time', $day_start_time )
                                        ->count() == 0
                                ) {
                                    $interval_valid = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        if ( ! $interval_valid ) {
            $result['interval_not_in_staff_schedule'] = true;
        }
        if ( $service ) {
            if ( $service_duration >= DAY_IN_SECONDS ) {
                // For services with duration 24+ hours check days off
                $service_schedule = (array) Lib\Proxy\ServiceSchedule::getSchedule( $service_id );
                $interval_valid   = true;

                // Check service schedule and service special days
                for ( $day = 0; $day < $service_duration / DAY_IN_SECONDS; $day ++ ) {
                    $work_date = date_create( $start_date )->modify( sprintf( '%s days', $day ) );
                    $week_day  = $work_date->format( 'w' ) + 1;
                    // Check service schedule for days off
                    $service_schedule_valid = true;
                    if ( Lib\Config::serviceScheduleActive() ) {
                        $service_schedule_valid = false;
                        foreach ( $service_schedule as $day_schedule ) {
                            if ( $day_schedule['day_index'] == $week_day && $day_schedule['start_time'] ) {
                                $service_schedule_valid = true;
                                break;
                            }
                        }
                    }
                    if ( ! $service_schedule_valid ) {
                        $interval_valid = false;
                        break;
                    }
                    // Check service special days for days off
                    $service_special_days_valid = true;
                    if ( Lib\Config::specialDaysEnabled() ) {
                        $special_days = (array) Lib\Proxy\SpecialDays::getServiceSchedule( $service_id, $work_date, $work_date );
                        if ( ! empty( $special_days ) ) {
                            $service_special_days_valid = false;
                            $schedule = current( $special_days );
                            if ( $schedule['start_time'] ) {
                                $service_special_days_valid = true;
                            }
                        }
                    }
                    if ( ! $service_special_days_valid ) {
                        $interval_valid = false;
                        break;
                    }
                }
                if ( ! $interval_valid ) {
                    $result['interval_not_in_service_schedule'] = true;
                }
                // Check staff schedule and staff special days
                $interval_valid = true;
                for ( $day = 0; $day < $service_duration / DAY_IN_SECONDS; $day ++ ) {
                    $work_date = date_create( $start_date )->modify( sprintf( '%s days', $day ) );
                    $week_day  = $work_date->format( 'w' ) + 1;
                    if ( Lib\Entities\StaffScheduleItem::query()
                            ->where( 'staff_id', $staff_id )
                            ->where( 'day_index', $week_day )
                            ->whereNot( 'start_time', null )
                            ->count() == 0
                    ) {
                        $interval_valid = false;
                        break;
                    }
                }
                if ( ! $interval_valid ) {
                    $result['interval_not_in_staff_schedule'] = true;
                }
            } else {
                // Check if selected interval fit into service schedule.
                $interval_valid = false;
                // Check day before and current day to get night schedule from previous day.
                for ( $day = 0; $day <= 1; $day ++ ) {
                    $day_start_date = date_create( $start_date )->modify( sprintf( '%s days', $day - 1 ) );
                    $day_end_date   = date_create( $end_date )->modify( sprintf( '%s days', $day - 1 ) );

                    $day_start_hour = ( 1 - $day ) * 24 + $day_start_date->format( 'G' );
                    $day_end_hour   = ( 1 - $day ) * 24 + $day_end_date->format( 'G' );
                    $day_start_time = sprintf( '%02d:%02d:00', $day_start_hour, $day_start_date->format( 'i' ) );
                    $day_end_time   = sprintf( '%02d:%02d:00', $day_end_hour >= $day_start_hour ? $day_end_hour : $day_end_hour + 24, $day_end_date->format( 'i' ) );

                    $special_days = (array) Lib\Proxy\SpecialDays::getServiceSchedule( $service_id, $day_start_date, $day_start_date );
                    if ( ! empty( $special_days ) ) {
                        // Check if interval fit into special day schedule.
                        $special_day = current( $special_days );
                        if ( ( $special_day['start_time'] <= $day_start_time ) && ( $special_day['end_time'] >= $day_end_time ) ) {
                            if ( ! ( $special_day['break_start'] && ( $special_day['break_start'] < $day_end_time ) && ( $special_day['break_end'] > $day_start_time ) ) ) {
                                $interval_valid = true;
                                break;
                            }
                        }
                    } else {
                        // Check if interval fit into service working schedule.
                        $schedule = (array) Lib\Proxy\ServiceSchedule::getSchedule( $service_id );
                        if ( ! empty ( $schedule ) ) {
                            $week_day = $day_start_date->format( 'w' ) + 1;
                            foreach ( $schedule as $schedule_day ) {
                                if ( $schedule_day['day_index'] == $week_day ) {
                                    if ( ( $schedule_day['start_time'] <= $day_start_time ) && ( $schedule_day['end_time'] >= $day_end_time ) ) {
                                        $interval_valid = true;
                                        if ( $schedule_day['break_start'] && ( $schedule_day['break_start'] < $day_end_time ) && ( $schedule_day['break_end'] > $day_start_time ) ) {
                                            $interval_valid = false;
                                            break;
                                        }
                                    }
                                }
                            }
                        } else {
                            $interval_valid = true;
                            break;
                        }
                    }
                }
                if ( ! $interval_valid ) {
                    $result['interval_not_in_service_schedule'] = true;
                }
                // Service duration interval is not equal to.
                $result['date_interval_warning'] = ! ( $appointment_duration >= $service->getMinDuration() && $appointment_duration <= $service->getMaxDuration() && ( $service_duration == 0 || $appointment_duration % $service_duration == 0 ) );
            }

            // Check customers for appointments limit
            if ( $start_date ) {
                foreach ( $customers as $index => $customer ) {
                    if ( $service->appointmentsLimitReached( $customer['id'], array( $start_date ) ) ) {
                        $customer_error = Lib\Entities\Customer::find( $customer['id'] );
                        $result['customers_appointments_limit'][] = sprintf( __( '%s has reached the limit of bookings for this service', 'bookly' ), $customer_error->getFullName() );
                    }
                }
            }
        }

        wp_send_json( $result );
    }

    /**
     * Get appointment for FullCalendar.
     *
     * @param integer $staff_id
     * @param int $appointment_id
     * @return array
     */
    private static function _getAppointmentForFC( $staff_id, $appointment_id )
    {
        $query = Lib\Entities\Appointment::query( 'a' )
            ->where( 'a.id', $appointment_id );

        $appointments = Calendar\Page::buildAppointmentsForFC( $staff_id, $query );

        return $appointments[0];
    }

    /**
     * Check whether interval is available for given appointment.
     *
     * @param $start_date
     * @param $end_date
     * @param $staff_id
     * @param $appointment_id
     * @return bool
     */
    private static function _dateIntervalIsAvailableForAppointment( $start_date, $end_date, $staff_id, $appointment_id )
    {
        return Lib\Entities\Appointment::query( 'a' )
            ->whereNot( 'a.id', $appointment_id )
            ->where( 'a.staff_id', $staff_id )
            ->whereLt( 'a.start_date', $end_date )
            ->whereGt( 'a.end_date', $start_date )
            ->count() == 0;
    }
}