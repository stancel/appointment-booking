<?php
namespace Bookly\Backend\Modules\Calendar;

use Bookly\Lib;

/**
 * Class Page
 * @package Bookly\Backend\Modules\Calendar
 */
class Page extends Lib\Base\Ajax
{
    /**
     * Render page.
     */
    public static function render()
    {
        /** @var \WP_Locale $wp_locale */
        global $wp_locale;

        self::enqueueStyles( array(
            'module'  => array( 'css/fullcalendar.min.css', ),
            'backend' => array( 'bootstrap/css/bootstrap-theme.min.css' ),
        ) );

        self::enqueueScripts( array(
            'backend' => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/alert.js' => array( 'jquery' ),
            ),
            'module' => array(
                'js/fullcalendar.min.js'   => array( 'bookly-moment.min.js' ),
                'js/fc-multistaff-view.js' => array( 'bookly-fullcalendar.min.js' ),
                'js/calendar-common.js'    => array( 'bookly-fc-multistaff-view.js' ),
                'js/calendar.js'           => array( 'bookly-calendar-common.js' ),
            ),
        ) );

        $slot_length_minutes = get_option( 'bookly_gen_time_slot_length', '15' );
        $slot = new \DateInterval( 'PT' . $slot_length_minutes . 'M' );

        $staff_members = Lib\Utils\Common::isCurrentUserAdmin()
            ? Lib\Entities\Staff::query()->sortBy( 'position' )->find()
            : Lib\Entities\Staff::query()->where( 'wp_user_id', get_current_user_id() )->find();

        wp_localize_script( 'bookly-calendar.js', 'BooklyL10n', array(
            'csrf_token'      => Lib\Utils\Common::getCsrfToken(),
            'slotDuration'    => $slot->format( '%H:%I:%S' ),
            'calendar'        => array(
                'shortMonths' => array_values( $wp_locale->month_abbrev ),
                'longMonths'  => array_values( $wp_locale->month ),
                'shortDays'   => array_values( $wp_locale->weekday_abbrev ),
                'longDays'    => array_values( $wp_locale->weekday ),
            ),
            'dpDateFormat'    => Lib\Utils\DateTime::convertFormat( 'date', Lib\Utils\DateTime::FORMAT_JQUERY_DATEPICKER ),
            'mjsDateFormat'   => Lib\Utils\DateTime::convertFormat( 'date', Lib\Utils\DateTime::FORMAT_MOMENT_JS ),
            'mjsTimeFormat'   => Lib\Utils\DateTime::convertFormat( 'time', Lib\Utils\DateTime::FORMAT_MOMENT_JS ),
            'today'           => __( 'Today', 'bookly' ),
            'week'            => __( 'Week',  'bookly' ),
            'day'             => __( 'Day',   'bookly' ),
            'month'           => __( 'Month', 'bookly' ),
            'allDay'          => __( 'All Day', 'bookly' ),
            'delete'          => __( 'Delete',  'bookly' ),
            'noStaffSelected' => __( 'No staff selected', 'bookly' ),
            'are_you_sure'    => __( 'Are you sure?',     'bookly' ),
            'startOfWeek'     => (int) get_option( 'start_of_week' ),
            'recurring_appointments' => array(
                'active' => (int) Lib\Config::recurringAppointmentsActive(),
                'title'  => __( 'Recurring appointments', 'bookly' ),
            ),
            'waiting_list'    => array(
                'active' => (int) Lib\Config::waitingListActive(),
                'title'  => __( 'On waiting list', 'bookly' ),
            ),
            'packages'    => array(
                'active' => (int) Lib\Config::packagesActive(),
                'title'  => __( 'Package', 'bookly' ),
            ),
        ) );

        self::renderTemplate( 'calendar', compact( 'staff_members' ) );
    }

    /**
     * Build appointments for FullCalendar.
     *
     * @param integer $staff_id
     * @param Lib\Query $query
     * @return mixed
     */
    public static function buildAppointmentsForFC( $staff_id, Lib\Query $query )
    {
        $one_participant   = '<div>' . str_replace( "\n", '</div><div>', get_option( 'bookly_cal_one_participant' ) ) . '</div>';
        $many_participants = '<div>' . str_replace( "\n", '</div><div>', get_option( 'bookly_cal_many_participants' ) ) . '</div>';
        $postfix_any       = sprintf( ' (%s)', get_option( 'bookly_l10n_option_employee' ) );
        $participants      = null;
        $default_codes     = array(
            '{amount_due}'        => '',
            '{amount_paid}'       => '',
            '{appointment_date}'  => '',
            '{appointment_time}'  => '',
            '{booking_number}'    => '',
            '{category_name}'     => '',
            '{client_email}'      => '',
            '{client_name}'       => '',
            '{client_first_name}' => '',
            '{client_last_name}'  => '',
            '{client_phone}'      => '',
            '{company_address}'   => get_option( 'bookly_co_address' ),
            '{company_name}'      => get_option( 'bookly_co_name' ),
            '{company_phone}'     => get_option( 'bookly_co_phone' ),
            '{company_website}'   => get_option( 'bookly_co_website' ),
            '{custom_fields}'     => '',
            '{extras}'            => '',
            '{extras_total_price}'=> 0,
            '{location_name}'     => '',
            '{location_info}'     => '',
            '{on_waiting_list}'   => '',
            '{payment_status}'    => '',
            '{payment_type}'      => '',
            '{service_capacity}'  => '',
            '{service_info}'      => '',
            '{service_name}'      => '',
            '{service_price}'     => '',
            '{signed_up}'         => '',
            '{staff_email}'       => '',
            '{staff_info}'        => '',
            '{staff_name}'        => '',
            '{staff_phone}'       => '',
            '{status}'            => '',
            '{total_price}'       => '',
        );
        $query
            ->select( 'a.id, a.series_id, a.staff_any, a.location_id, a.start_date, DATE_ADD(a.end_date, INTERVAL a.extras_duration SECOND) AS end_date,
                COALESCE(s.title,a.custom_service_name) AS service_name, COALESCE(s.color,"silver") AS service_color, s.info AS service_info,
                COALESCE(ss.price,a.custom_service_price) AS service_price,
                st.full_name AS staff_name, st.email AS staff_email, st.info AS staff_info, st.phone AS staff_phone,
                (SELECT SUM(ca.number_of_persons) FROM ' . Lib\Entities\CustomerAppointment::getTableName() . ' ca WHERE ca.appointment_id = a.id) AS total_number_of_persons,
                ca.number_of_persons,
                ca.units,
                ca.custom_fields,
                ca.status AS appointment_status,
                ca.extras,
                ca.package_id,
                ct.name AS category_name,
                c.full_name AS client_name, c.first_name AS client_first_name, c.last_name AS client_last_name, c.phone AS client_phone, c.email AS client_email, c.id AS customer_id,
                p.total, p.type AS payment_gateway, p.status AS payment_status, p.paid,
                (SELECT SUM(ca.number_of_persons) FROM ' . Lib\Entities\CustomerAppointment::getTableName() . ' ca WHERE ca.appointment_id = a.id AND ca.status = "waitlisted") AS on_waiting_list' )
            ->leftJoin( 'CustomerAppointment', 'ca', 'ca.appointment_id = a.id' )
            ->leftJoin( 'Customer', 'c', 'c.id = ca.customer_id' )
            ->leftJoin( 'Payment', 'p', 'p.id = ca.payment_id' )
            ->leftJoin( 'Service', 's', 's.id = a.service_id' )
            ->leftJoin( 'Category', 'ct', 'ct.id = s.category_id' )
            ->leftJoin( 'Staff', 'st', 'st.id = a.staff_id' )
            ->leftJoin( 'StaffService', 'ss', 'ss.staff_id = a.staff_id AND ss.service_id = a.service_id' )
            ->groupBy( 'a.id' );

        if ( Lib\Config::groupBookingActive() ) {
            $query->addSelect( 'COALESCE(ss.capacity_max,9999) AS service_capacity' );
        } else {
            $query->addSelect( '1 AS service_capacity' );
        }

        $appointments =  $query->fetchArray();

        foreach ( $appointments as $key => $appointment ) {
            $codes = $default_codes;
            $codes['{appointment_date}'] = Lib\Utils\DateTime::formatDate( $appointment['start_date'] );
            $codes['{appointment_time}'] = Lib\Utils\DateTime::formatTime( $appointment['start_date'] );
            $codes['{booking_number}']   = $appointment['id'];
            $codes['{on_waiting_list}']  = $appointment['on_waiting_list'];
            $codes['{service_name}']     = $appointment['service_name'] ?  esc_html( $appointment['service_name'] ) : __( 'Untitled', 'bookly' );
            $codes['{service_price}']    = Lib\Utils\Price::format( $appointment['service_price'] * $appointment['units'] );
            $codes['{signed_up}']        = $appointment['total_number_of_persons'];
            foreach ( array( 'staff_name', 'staff_phone', 'staff_info', 'staff_email', 'service_info', 'service_capacity', 'category_name' ) as $field ) {
                $codes[ '{' . $field . '}' ] = esc_html( $appointment[ $field ] );
            }
            if ( $appointment['staff_any'] ) {
                $codes['{staff_name}'] .= $postfix_any;
            }
            // Display customer information only if there is 1 customer. Don't confuse with number_of_persons.
            if ( $appointment['number_of_persons'] == $appointment['total_number_of_persons'] ) {
                $participants = 'one';
                $template     = $one_participant;
                foreach ( array( 'client_name', 'client_first_name', 'client_last_name', 'client_phone', 'client_email' ) as $data_entry ) {
                    if ( $appointment[ $data_entry ] ) {
                        $codes[ '{' . $data_entry . '}' ] = esc_html( $appointment[ $data_entry ] );
                    }
                }

                // Payment.
                if ( $appointment['total'] ) {
                    $codes['{total_price}']    = Lib\Utils\Price::format( $appointment['total'] );
                    $codes['{amount_paid}']    = Lib\Utils\Price::format( $appointment['paid'] );
                    $codes['{amount_due}']     = Lib\Utils\Price::format( $appointment['total'] - $appointment['paid'] );
                    $codes['{total_price}']    = Lib\Utils\Price::format( $appointment['total'] );
                    $codes['{payment_type}']   = Lib\Entities\Payment::typeToString( $appointment['payment_gateway'] );
                    $codes['{payment_status}'] = Lib\Entities\Payment::statusToString( $appointment['payment_status'] );
                }
                // Status.
                $codes['{status}'] = Lib\Entities\CustomerAppointment::statusToString( $appointment['appointment_status'] );
            } else {
                $participants = 'many';
                $template     = $many_participants;
            }

            $codes = Proxy\Shared::prepareAppointmentCodesData( $codes, $appointment, $participants );

            $appointments[ $key ] = array(
                'id'         => $appointment['id'],
                'start'      => $appointment['start_date'],
                'end'        => $appointment['end_date'],
                'title'      => ' ',
                'desc'       => strtr( $template, $codes ),
                'color'      => $appointment['service_color'],
                'staffId'    => $staff_id,
                'series_id'  => (int) $appointment['series_id'],
                'package_id' => (int) $appointment['package_id'],
                'waitlisted' => (int) $appointment['on_waiting_list'],
                'staff_any'  => (int) $appointment['staff_any'],
            );
        }

        return $appointments;
    }
}