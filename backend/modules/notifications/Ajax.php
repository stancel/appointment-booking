<?php
namespace Bookly\Backend\Modules\Notifications;

use Bookly\Lib;

/**
 * Class Ajax
 * @package Bookly\Backend\Modules\Notifications
 */
class Ajax extends Lib\Base\Ajax
{
    /**
     * Get notifications data.
     */
    public static function getEmailNotificationsData()
    {
        $form = new Forms\Notifications( 'email' );

        $bookly_email_sender_name  = get_option( 'bookly_email_sender_name' ) == '' ?
            get_option( 'blogname' )    : get_option( 'bookly_email_sender_name' );

        $bookly_email_sender = get_option( 'bookly_email_sender' ) == '' ?
            get_option( 'admin_email' ) : get_option( 'bookly_email_sender' );

        $notifications = array();
        foreach ( $form->getData() as $notification ) {
            $name = Lib\Entities\Notification::getName( $notification['type'] );
            if ( in_array( $notification['type'], Lib\Entities\Notification::getCustomNotificationTypes() ) && $notification['subject'] != '' ) {
                // In window Test Email Notification
                // for custom notification, subject is name.
                $name = $notification['subject'];
            }
            $notifications[] = array(
                'id'     => $notification['id'],
                'name'   => $name,
                'active' => $notification['active'],
            );
        }

        $result = array(
            'notifications' => $notifications,
            'sender_email'  => $bookly_email_sender,
            'sender_name'   => $bookly_email_sender_name,
            'send_as'       => get_option( 'bookly_email_send_as' ),
            'reply_to_customers' => get_option( 'bookly_email_reply_to_customers' ),
        );

        wp_send_json_success( $result );
    }

    /**
     * Test email notifications.
     */
    public static function testEmailNotifications()
    {
        $to_email      = self::parameter( 'to_email' );
        $sender_name   = self::parameter( 'sender_name' );
        $sender_email  = self::parameter( 'sender_email' );
        $send_as       = self::parameter( 'send_as' );
        $notifications = self::parameter( 'notifications' );
        $reply_to_customers = self::parameter( 'reply_to_customers' );

        // Change 'Content-Type' and 'Reply-To' for test email notification.
        add_filter( 'bookly_email_headers', function ( $headers ) use ( $sender_name, $sender_email, $send_as, $reply_to_customers ) {
            $headers = array();
            if ( $send_as == 'html' ) {
                $headers[] = 'Content-Type: text/html; charset=utf-8';
            } else {
                $headers[] = 'Content-Type: text/plain; charset=utf-8';
            }
            $headers[] = 'From: ' . $sender_name . ' <' . $sender_email . '>';
            if ( $reply_to_customers ) {
                $headers[] = 'Reply-To: ' . $sender_name . ' <' . $sender_email . '>';
            }

            return $headers;
        }, 10, 1 );

        Lib\NotificationSender::sendTestEmailNotifications( $to_email, $notifications, $send_as );

        wp_send_json_success();
    }

    /**
     * Create new custom notification
     */
    public static function createCustomNotification()
    {
        $notification = new Lib\Entities\Notification();
        $notification
            ->setType( Lib\Entities\Notification::TYPE_APPOINTMENT_START_TIME )
            ->setToCustomer( 1 )
            ->setToStaff( 1 )
            ->setSettings( json_encode( Lib\DataHolders\Notification\Settings::getDefault() ) )
            ->setGateway( 'email' )
            ->save();

        $notification = $notification->getFields();
        $id   = $notification['id'];
        $html = '';
        if ( self::parameter( 'render' ) ) {
            $form     = new Forms\Notifications( 'email' );
            $statuses = Lib\Entities\CustomerAppointment::getStatuses();

            $html = self::renderTemplate( '_custom_notification', compact( 'form', 'notification', 'statuses' ), false );
        }
        wp_send_json_success( compact( 'html', 'id' ) );
    }

    /**
     * Delete custom notification
     */
    public static function deleteCustomNotification()
    {
        $id = self::parameter( 'id' );
        Lib\Entities\Notification::query()
            ->delete()
            ->where( 'id', $id )
            ->whereIn( 'type', Lib\Entities\Notification::getCustomNotificationTypes() )
            ->execute();

        wp_send_json_success();
    }
}