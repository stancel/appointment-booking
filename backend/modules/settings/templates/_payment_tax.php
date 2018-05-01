<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
\Bookly\Lib\Utils\Common::optionToggle( 'bookly_' . $system . '_send_tax', __( 'Send tax information', 'bookly' ), '',
    array(
        array( 0, __( 'No',  'bookly' ) ),
        array( 1, __( 'Yes', 'bookly' ) ),
    ) );