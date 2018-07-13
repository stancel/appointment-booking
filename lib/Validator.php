<?php
namespace Bookly\Lib;

/**
 * Class Validator
 * @package Bookly\Lib
 */
class Validator
{
    private $errors = array();

    /**
     * Validate email.
     *
     * @param string $field
     * @param array $data
     */
    public function validateEmail( $field, $data )
    {
        if ( $data['email'] == '' && ( Config::emailRequired() || get_option( 'bookly_cst_create_account', 0 ) ) ) {
            $this->errors[ $field ] = Utils\Common::getTranslatedOption( 'bookly_l10n_required_email' );
        } else {
            if ( $data['email'] != '' && ! is_email( $data['email'] ) ) {
                $this->errors[ $field ] = __( 'Invalid email', 'bookly' );
            }
            // Check email for uniqueness when a new WP account is going to be created.
            if ( get_option( 'bookly_cst_create_account', 0 ) && ! get_current_user_id() ) {
                $customer = new Entities\Customer();
                // Try to find customer by phone or email.
                $customer->loadBy(
                    Config::phoneRequired()
                        ? array( 'phone' => $data['phone'] )
                        : array( 'email' => $data['email'] )
                );
                if ( ( ! $customer->isLoaded() || ! $customer->getWpUserId() ) && email_exists( $data['email'] ) ) {
                    $this->errors[ $field ] = __( 'This email is already in use', 'bookly' );
                }
            }
        }
    }

    /**
     * @param string $field_name
     * @param array $data
     */
    public function validateBirthdayDay( $field_name, array $data )
    {
        $day    = (int) $data['birthday_day'];
        $month  = (int) $data['birthday_month'];
        $year   = (int) $data['birthday_year'];

        $last_day = (int) date( 't', strtotime( $year . '-' . $month . '-01' ) );

        if ( $day < 1 ) {
            $this->errors[ $field_name ] = Utils\Common::getTranslatedOption( 'bookly_l10n_required_day' );
        } elseif ( $day > $last_day ) {
            $this->errors[ $field_name ] = Utils\Common::getTranslatedOption( 'bookly_l10n_invalid_day' );
        }
    }

    /**
     * @param string $field_name
     * @param int $month
     */
    public function validateBirthdayMonth( $field_name, $month )
    {
        $month = (int) $month;

        if ( $month < 1 || $month > 12 ) {
            $this->errors[ $field_name ] = Utils\Common::getTranslatedOption( 'bookly_l10n_required_month' );
        }
    }

    /**
     * @param string $field_name
     * @param int $year
     */
    public function validateBirthdayYear( $field_name, $year )
    {
        $year = (int) $year;
        $max  = (int) Slots\DatePoint::now()->format( 'Y' );
        $min  = $max - 100;

        if ( $year < $min || $year > $max ) {
            $this->errors[ $field_name ] = Utils\Common::getTranslatedOption( 'bookly_l10n_required_year' );
        }
    }

    /**
     * @param string $field_name
     * @param string $value
     * @param bool $required
     */
    public function validateAddress( $field_name, $value, $required = false )
    {
        $value = trim( $value );
        if ( empty( $value ) && $required ) {
            $this->errors[ $field_name ] = Utils\Common::getTranslatedOption( 'bookly_l10n_required_' . $field_name );
        }
    }

    /**
     * Validate phone.
     *
     * @param string $field
     * @param string $phone
     * @param bool $required
     */
    public function validatePhone( $field, $phone, $required = false )
    {
        if ( $phone == '' && $required ) {
            $this->errors[ $field ] = Utils\Common::getTranslatedOption( 'bookly_l10n_required_phone' );
        }
    }

    /**
     * Validate name.
     *
     * @param string $field
     * @param string $name
     */
    public function validateName( $field, $name )
    {
        if ( $name != '' ) {
            $max_length = 255;
            if ( preg_match_all( '/./su', $name ) > $max_length ) {
                $this->errors[ $field ] = sprintf(
                    __( '"%s" is too long (%d characters max).', 'bookly' ),
                    $name,
                    $max_length
                );
            }
        } else {
            switch ( $field ) {
                case 'full_name' :
                    $this->errors[ $field ] = Utils\Common::getTranslatedOption( 'bookly_l10n_required_name' );
                    break;
                case 'first_name' :
                    $this->errors[ $field ] = Utils\Common::getTranslatedOption( 'bookly_l10n_required_first_name' );
                    break;
                case 'last_name' :
                    $this->errors[ $field ] = Utils\Common::getTranslatedOption( 'bookly_l10n_required_last_name' );
                    break;
            }
        }
    }

    /**
     * Validate number.
     *
     * @param string $field
     * @param mixed $number
     * @param bool $required
     */
    public function validateNumber( $field, $number, $required = false )
    {
        if ( $number != '' ) {
            if ( ! is_numeric( $number ) ) {
                $this->errors[ $field ] = __( 'Invalid number', 'bookly' );
            }
        } elseif ( $required ) {
            $this->errors[ $field ] = __( 'Required', 'bookly' );
        }
    }

    /**
     * Validate date.
     *
     * @param string $field
     * @param string $date
     * @param bool $required
     */
    public function validateDate( $field, $date, $required = false )
    {
        if ( $date != '' ) {
            if ( date_create( $date ) === false ) {
                $this->errors[ $field ] = __( 'Invalid date', 'bookly' );
            }
        } elseif ( $required ) {
            $this->errors[ $field ] = __( 'Required', 'bookly' );
        }
    }

    /**
     * Validate time.
     *
     * @param string $field
     * @param string $time
     * @param bool $required
     */
    public function validateTime( $field, $time, $required = false )
    {
        if ( $time != '' ) {
            if ( ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
                $this->errors[ $field ] = __( 'Invalid time', 'bookly' );
            }
        } elseif ( $required ) {
            $this->errors[ $field ] = __( 'Required', 'bookly' );
        }
    }

    /**
     * Post-validate customer.
     *
     * @param array $data
     * @param UserBookingData $userData
     */
    public function postValidateCustomer( $data, UserBookingData $userData )
    {
        if ( empty ( $this->errors ) ) {
            $user_id  = get_current_user_id();
            $customer = new Entities\Customer();
            if ( $user_id > 0 ) {
                // Try to find customer by WP user ID.
                $customer->loadBy( array( 'wp_user_id' => $user_id ) );
            }
            if ( ! $customer->isLoaded() ) {
                if ( Config::showFacebookLoginButton() && $userData->getFacebookId() ) {
                    // Try to find customer by Facebook ID.
                    $customer->loadBy( array( 'facebook_id' => $userData->getFacebookId() ) );
                }
                if ( ! $customer->isLoaded() ) {
                    // Try to find customer by 'primary' identifier.
                    $identifier = Config::phoneRequired() ? 'phone' : 'email';
                    $customer->loadBy( array( $identifier => $data[ $identifier ] ) );
                    if ( ! $customer->isLoaded() ) {
                        // Try to find customer by 'secondary' identifier.
                        $identifier = Config::phoneRequired() ? 'email' : 'phone';
                        $customer->loadBy( array( 'phone' => '', 'email' => '', $identifier => $data[ $identifier ] ) );
                    }
                    if ( Config::allowDuplicates() ) {
                        $customer_data = array(
                            'email' => $data['email'],
                            'phone' => $data['phone'],
                        );
                        if ( Config::showFirstLastName() ) {
                            $customer_data['first_name'] = $data['first_name'];
                            $customer_data['last_name']  = $data['last_name'];
                        } else {
                            $customer_data['full_name'] = $data['full_name'];
                        }
                        $customer->loadBy( $customer_data );
                    } elseif ( ! isset ( $data['force_update_customer'] ) && $customer->isLoaded() ) {
                        // Find difference between new and existing data.
                        $diff   = array();
                        $fields = array(
                            'phone' => Utils\Common::getTranslatedOption( 'bookly_l10n_label_phone' ),
                            'email' => Utils\Common::getTranslatedOption( 'bookly_l10n_label_email' )
                        );
                        $current = $customer->getFields();
                        if ( Config::showFirstLastName() ) {
                            $fields['first_name'] = Utils\Common::getTranslatedOption( 'bookly_l10n_label_first_name' );
                            $fields['last_name']  = Utils\Common::getTranslatedOption( 'bookly_l10n_label_last_name' );
                        } else {
                            $fields['full_name'] = Utils\Common::getTranslatedOption( 'bookly_l10n_label_name' );
                        }
                        foreach ( $fields as $field => $name ) {
                            if (
                                $data[ $field ] != '' &&
                                $current[ $field ] != '' &&
                                $data[ $field ] != $current[ $field ]
                            ) {
                                $diff[] = $name;
                            }
                        }
                        if ( ! empty ( $diff ) ) {
                            $this->errors['customer'] = sprintf(
                                __( 'Your %s: %s is already associated with another %s.<br/>Press Update if we should update your user data, or press Cancel to edit entered data.', 'bookly' ),
                                $fields[ $identifier ],
                                $data[ $identifier ],
                                implode( ', ', $diff )
                            );
                        }
                    }
                }
            }
            if ( $customer->isLoaded() ) {
                // Check appointments limit
                $data = array();
                foreach ( $userData->cart->getItems() as $cart_item ) {
                    if ( $cart_item->toBePutOnWaitingList() ) {
                        // Skip waiting list items.
                        continue;
                    }

                    $service = $cart_item->getService();
                    $slots   = $cart_item->getSlots();

                    $data[ $service->getId() ]['service'] = $service;
                    $data[ $service->getId() ]['dates'][] = $slots[0][2];
                }
                foreach ( $data as $service_data ) {
                    if ( $service_data['service']->appointmentsLimitReached( $customer->getId(), $service_data['dates'] ) ) {
                        $this->errors['appointments_limit_reached'] = true;
                        break;
                    }
                }
            }
        }
    }

    /**
     * Validate info fields.
     *
     * @param array $info_fields
     */
    public function validateInfoFields( array $info_fields )
    {
        $this->errors = Proxy\CustomerInformation::validate( $this->errors, $info_fields );
    }

    /**
     * Validate cart.
     *
     * @param array $cart
     * @param int $form_id
     */
    public function validateCart( $cart, $form_id )
    {
        foreach ( $cart as $cart_key => $cart_parameters ) {
            foreach ( $cart_parameters as $parameter => $value ) {
                switch ( $parameter ) {
                    case 'custom_fields':
                        $this->errors = Proxy\CustomFields::validate( $this->errors, $value, $form_id, $cart_key );
                        break;
                }
            }
        }
    }

    /**
     * Get errors.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}