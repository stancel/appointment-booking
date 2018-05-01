<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib\Base;
use Bookly\Lib\CartInfo;

/**
 * Class DepositPayments
 * Invoke local methods from Deposit Payments Standard add-on.
 *
 * @package Bookly\Lib\Proxy
 *
 * @method static string formatDeposit( double $deposit_amount, string $deposit ) Return formatted deposit amount
 * @see \BooklyDepositPayments\Lib\ProxyProviders\Local::formatDeposit()
 *
 * @method static double|string prepareAmount( double $amount, string $deposit, int $number_of_persons ) Return deposit amount for all persons
 * @see \BooklyDepositPayments\Lib\ProxyProviders\Local::prepareAmount()
 *
 * @method static void renderStaffServiceLabel() Render column header for deposit
 * @see \BooklyDepositPayments\Lib\ProxyProviders\Local::renderStaffServiceLabel()
 *
 * @method static void renderStaffCabinetSettings() Render deposit in PopUp for short_code settings
 * @see \BooklyDepositPayments\Lib\ProxyProviders\Local::renderStaffCabinetSettings()
 *
 * @method static void renderPayNowRow( CartInfo $cart_info, array $table, string $layout ) Render "Pay now" row on a Cart step
 * @see \BooklyDepositPayments\Lib\ProxyProviders\Local::renderPayNowRow()
 */
abstract class DepositPayments extends Base\ProxyInvoker
{

}