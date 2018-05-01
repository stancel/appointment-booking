<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib\Base;

/**
 * Class Coupons
 * Invoke local methods from Invoices add-on.
 *
 * @method static void renderDownloadInvoicesButton()
 * @see \BooklyInvoices\Lib\ProxyProviders\Local::renderDownloadInvoicesButton()
 *
 * @method static void renderNotificationAttach( array $notification )
 * @see \BooklyInvoices\Lib\ProxyProviders\Local::renderNotificationAttach()
 *
 * @method static string|null getInvoice( \Bookly\Lib\Entities\Payment $payment )
 * @see \BooklyInvoices\Lib\ProxyProviders\Local::getInvoice()
 *
 * @method static void downloadInvoice( \Bookly\Lib\Entities\Payment $payment )
 * @see \BooklyInvoices\Lib\ProxyProviders\Local::downloadInvoice()
 *
 * @package Bookly\Lib\Proxy
 */
abstract class Invoices extends Base\ProxyInvoker
{
}