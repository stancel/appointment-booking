<?php
namespace Bookly\Lib;

/**
 * Class CartInfo
 * @package Bookly\Lib\Booking
 */
class CartInfo
{
    /** @var float */
    protected $deposit = 0;
    /** @var float */
    protected $waiting_list_total = 0;
    /** @var float */
    protected $waiting_list_deposit = 0;
    /** @var float */
    protected $subtotal = 0;
    /** @var float */
    protected $subtotal_tax = 0;
    /** @var float */
    protected $group_discount = 0;
    /** @var float */
    protected $coupon_discount = 0;
    /** @var float */
    protected $pay_now = 0;
    /** @var float */
    protected $total = 0;
    /** @var array [['deposit' => float, 'total' => float, 'allow_coupon' => bool]]*/
    protected $amounts_taxable = array();
    /** @var float absolute amount */
    protected $price_correction = 0;
    /** @var */
    protected $payment_method_send_tax;
    /** @var */
    protected $payment_method_calculate_rule;

    /** @var float */
    private $pay_tax;
    /** @var float */
    private $total_tax;

    /** @var \BooklyCoupons\Lib\Entities\Coupon|false */
    private $coupon = false;
    /** @var UserBookingData */
    private $userData;
    /** @var bool */
    private $tax_included = true;

    public function __construct( UserBookingData $userData )
    {
        $this->userData = $userData;
        if ( Config::taxesEnabled() ) {
            $this->tax_included = get_option( 'bookly_taxes_in_price' ) != 'excluded';
        }
    }

    /**
     * Gets coupon
     *
     * @return \BooklyCoupons\Lib\Entities\Coupon
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * Sets coupon
     *
     * @param \BooklyCoupons\Lib\Entities\Coupon|null $coupon
     * @return $this
     */
    public function setCoupon( $coupon )
    {
        $this->coupon = $coupon;

        return $this;
    }

    public function calculate()
    {
        $coupon_total = 0;
        foreach ( $this->userData->cart->getItems() as $key => $item ) {
            if (
                $item->getSeriesUniqueId()
                && get_option( 'bookly_recurring_appointments_payment' ) === 'first'
                && ( ! $item->getFirstInSeries() )
            ) {
                continue;
            }

            // Cart contains a service that was already removed/deleted from Bookly (WooCommerce BP-224)
            if ( $item->getService() ) {
                $item_price = $item->getServicePrice( $item->getNumberOfPersons() );
                if ( Config::waitingListEnabled() && $item->toBePutOnWaitingList() ) {
                    $this->waiting_list_total   += $item_price;
                    $this->waiting_list_deposit += Proxy\DepositPayments::prepareAmount( $item_price, $item->getDeposit(), $item->getNumberOfPersons() );
                } else {
                    $allow_coupon = false;
                    if ( $this->coupon && $this->coupon->validForCartItem( $item ) ) {
                        $coupon_total += $item_price;
                        $allow_coupon  = true;
                    }
                    $this->subtotal += $item_price;
                    $this->deposit  += Proxy\DepositPayments::prepareAmount( $item_price, $item->getDeposit(), $item->getNumberOfPersons() );
                    if ( ! $item->toBePutOnWaitingList() ) {
                        $this->subtotal_tax   += (float) Proxy\Taxes::getTaxAmount( $item );
                        $this->amounts_taxable = Proxy\Taxes::prepareTaxRateAmounts( $this->amounts_taxable, $item, $allow_coupon );
                    }
                }
            }
        }

        $this->total = $this->subtotal;

        if ( $this->coupon ) {
            $this->coupon_discount = $this->coupon->apply( $coupon_total ) - $coupon_total;
            $this->total += $this->coupon_discount;
        }

        $total_without_group_discount = $this->total;
        $this->total = Proxy\CustomerGroups::prepareCartTotalPrice( $total_without_group_discount, $this->userData );
        $this->group_discount = $this->total - $total_without_group_discount;
    }

    /**
     * @return float
     */
    public function getSubtotal()
    {
        return $this->subtotal;
    }

    /**
     * @return float
     */
    public function getSubtotalTax()
    {
        return $this->subtotal_tax;
    }

    /**
     * @return float
     */
    public function getDeposit()
    {
        return $this->deposit;
    }

    /**
     * @return float
     */
    public function getDue()
    {
        if ( Config::depositPaymentsEnabled() && ! $this->userData->getDepositFull() ) {
            return $this->getTotal() - $this->getDepositPay();
        }

        return 0;
    }

    /**
     * Gets waiting_list_total
     *
     * @return float
     */
    public function getWaitingListTotal()
    {
        return $this->waiting_list_total;
    }

    /**
     * Gets waiting_list_deposit
     *
     * @return float
     */
    public function getWaitingListDeposit()
    {
        return $this->waiting_list_deposit;
    }

    /**
     * Sets price_correction
     *
     * @param float $increase
     * @param float $addition
     * @return $this
     */
    public function setPriceCorrection( $increase, $addition )
    {
        $this->price_correction = 0;
        $amount = $this->getPayNow();
        if ( $amount > 0 ) {
            $this->price_correction = Utils\Price::correction( $amount, - (float) $increase, - (float) $addition ) - $amount;
        }

        return $this;
    }

    /**
     * Gets price_correction
     *
     * @return float
     */
    public function getPriceCorrection()
    {
        return $this->price_correction;
    }

    /**
     * @param bool $send_tax
     * @param string $calculate_rule ['tax_increases_the_cost','tax_in_the_price']
     */
    public function setPaymentMethodSettings( $send_tax, $calculate_rule )
    {
        $this->payment_method_send_tax       = $send_tax;
        $this->payment_method_calculate_rule = $calculate_rule;
    }

    /**
     * @return float
     */
    public function getPaymentSystemPayNow()
    {
        $deposit = $this->userData->getDepositFull() ? $this->total : $this->deposit;
        switch ( $this->payment_method_calculate_rule ) {
            case 'tax_increases_the_cost':
                if ( $this->payment_method_send_tax ) {
                    if ( $this->tax_included ) {
                        if ( $deposit < $this->total ) {
                            $amount = $deposit - $this->getDepositTax();
                        } else {
                            $amount = $this->total - $this->getTotalTax();
                        }
                    } else {
                        $amount = min( $deposit, $this->total );
                    }

                    return $amount + $this->price_correction;
                }

                return $this->getPayNow();
            case 'tax_in_the_price':
            case 'tax_is_rate_of_the_price':
                return $this->getPayNow();
        }

        return $this->getPayNow();
    }

    /**
     * @return float|int
     */
    public function getPaymentSystemPayTax()
    {
        switch ( $this->payment_method_calculate_rule ) {
            case 'tax_in_the_price':
            case 'tax_increases_the_cost':
            case 'tax_is_rate_of_the_price':
                return $this->payment_method_send_tax
                    ? $this->getPayTax()
                    : 0;
        }
        return 0;
    }

    /**
     * Tax rate for Payson payment system.
     *
     * @return float|int
     */
    public function getPaymentSystemTaxRate()
    {
        if ( $this->payment_method_calculate_rule == 'tax_is_rate_of_the_price' ) {
            return $this->payment_method_send_tax
                ? $this->getPaymentSystemPayTax() / $this->getDepositPay()
                : 0;
        }

        return 0;
    }

    /**
     * Gets group_discount
     *
     * @return float
     */
    public function getGroupDiscount()
    {
        return $this->group_discount;
    }

    /**************************************************************************
     * Private                                                                *
     **************************************************************************/

    /**
     * @return float
     */
    private function getDiscount()
    {
        return $this->coupon_discount + $this->group_discount + $this->price_correction;
    }

    /**************************************************************************
     * Amounts dependent on taxes                                             *
     **************************************************************************/

    /**
     * @return mixed
     */
    public function getPayNow()
    {
        return $this->userData->getDepositFull() ? $this->getTotal() : min( $this->getDepositPay(), $this->getTotal() );
    }

    /**
     * @return mixed
     */
    public function getPayTax()
    {
        return $this->userData->getDepositFull() ? $this->getTotalTax() : min( $this->getDepositTax(), $this->getTotalTax() );
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        if ( $this->tax_included ) {
            return $this->subtotal + $this->getDiscount();
        } else {
            return $this->subtotal + $this->getDiscount() + $this->getTotalTax();
        }
    }

    /**
     * @return float|int
     */
    public function getTotalNoTax()
    {
        $total_no_tax = $this->subtotal + $this->getDiscount();
        if ( $this->tax_included ) {
            $total_no_tax -= $this->getTotalTax();
        }

        return $total_no_tax;
    }

    /**
     * @return float|int
     */
    public function getTotalTax()
    {
        if ( $this->total_tax == null ) {
            $taxes = array(
                'allow_coupon'   => 0,
                'without_coupon' => 0,
            );
            $coupon_total = 0;
            array_walk( $this->amounts_taxable, function ( $amount ) use ( &$taxes, &$coupon_total ) {
                if ( $amount['allow_coupon'] ) {
                    $taxes['allow_coupon']   += Proxy\Taxes::calculateTax( $amount['total'], $amount['rate'] );
                    $coupon_total += $amount['total'];
                } else {
                    $taxes['without_coupon'] += Proxy\Taxes::calculateTax( $amount['total'], $amount['rate'] );
                }
            } );

            if ( $coupon_total > 0 ) {
                $tax_products_with_coupon  = 1 - ( $this->coupon->getDiscount() / 100 + $this->coupon->getDeduction() / $coupon_total );
                $tax_products_with_coupon *= $taxes['allow_coupon'];
            } else {
                $tax_products_with_coupon  = 0;
            }

            $this->total_tax = $tax_products_with_coupon + $taxes['without_coupon'];
            if ( $this->group_discount != 0 ) {
                $group_discount_percent = $this->group_discount / ( $this->total - $this->group_discount ) * 100;
                $this->total_tax        = Utils\Price::correction( $this->total_tax, - $group_discount_percent, 0 );
            }

            $this->total_tax = round( $this->total_tax, 2 );
        }

        return $this->total_tax;
    }

    /**
     * @return float
     */
    public function getDepositPay()
    {
        if ( $this->tax_included ) {
            return min( $this->deposit, $this->total ) + $this->price_correction;
        } else {
            return min( ( $this->deposit + $this->getDepositTax() ), ( $this->total + $this->getTotalTax() ) ) + $this->price_correction;
        }
    }

    /**
     * @return float
     */
    private function getDepositTax()
    {
        if ( $this->pay_tax === null ) {
            $taxes_without_coupon = 0;
            foreach ( $this->amounts_taxable as $amount ) {
                $taxes_without_coupon += Proxy\Taxes::calculateTax( $amount['deposit'], $amount['rate'] );
            }
            $this->pay_tax = $taxes_without_coupon;
        }

        return $this->pay_tax;
    }

}