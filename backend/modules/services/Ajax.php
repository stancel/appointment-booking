<?php
namespace Bookly\Backend\Modules\Services;

use Bookly\Lib;

/**
 * Class Ajax
 * @package Bookly\Backend\Modules\Services
 */
class Ajax extends Page
{
    /**
     * Get category services
     */
    public static function getCategoryServices()
    {
        wp_send_json_success( self::renderTemplate( '_list', self::_getCaSeStSpCollections(), false ) );
    }

    /**
     * Add category.
     */
    public static function addCategory()
    {
        $html = '';
        if ( ! empty ( $_POST ) ) {
            if ( self::csrfTokenValid() ) {
                $form = new Forms\Category();
                $form->bind( self::postParameters() );
                if ( $category = $form->save() ) {
                    $html = self::renderTemplate( '_category_item', array( 'category' => $category->getFields() ), false );
                }
            }
        }
        wp_send_json_success( compact( 'html' ) );
    }

    /**
     * Update category.
     */
    public static function updateCategory()
    {
        $form = new Forms\Category();
        $form->bind( self::postParameters() );
        $form->save();
    }

    /**
     * Update category position.
     */
    public static function updateCategoryPosition()
    {
        $category_sorts = self::parameter( 'position' );
        foreach ( $category_sorts as $position => $category_id ) {
            $category_sort = new Lib\Entities\Category();
            $category_sort->load( $category_id );
            $category_sort->setPosition( $position );
            $category_sort->save();
        }
    }

    /**
     * Update services position.
     */
    public static function updateServicesPosition()
    {
        $services_sorts = self::parameter( 'position' );
        foreach ( $services_sorts as $position => $service_ids ) {
            $services_sort = new Lib\Entities\Service();
            $services_sort->load( $service_ids );
            $services_sort->setPosition( $position );
            $services_sort->save();
        }
    }

    /**
     * Reorder staff preferences for service
     */
    public static function updateServiceStaffPreferenceOrders()
    {
        $service_id  = self::parameter( 'service_id' );
        $positions = (array) self::parameter( 'positions' );
        /** @var Lib\Entities\StaffPreferenceOrder[] $staff_preferences */
        $staff_preferences = Lib\Entities\StaffPreferenceOrder::query()
            ->where( 'service_id', $service_id )
            ->indexBy( 'staff_id' )
            ->find();
        foreach ( $positions as $position => $staff_id ) {
            if ( array_key_exists( $staff_id, $staff_preferences ) ) {
                $staff_preferences[ $staff_id ]->setPosition( $position )->save();
            } else {
                $preference = new Lib\Entities\StaffPreferenceOrder();
                $preference
                    ->setServiceId( $service_id )
                    ->setStaffId( $staff_id )
                    ->setPosition( $position )
                    ->save();
            }
        }

        wp_send_json_success();
    }

    /**
     * Delete category.
     */
    public static function deleteCategory()
    {
        $category = new Lib\Entities\Category();
        $category->setId( self::parameter( 'id', 0 ) );
        $category->delete();
    }

    /**
     * Add service.
     */
    public static function addService()
    {
        $form = new Forms\Service();
        $form->bind( self::postParameters() );
        $form->getObject()->setDuration( Lib\Config::getTimeSlotLength() );
        $service = $form->save();
        $data = self::_getCaSeStSpCollections( $service->getCategoryId() );

        Proxy\Shared::serviceCreated( $service, self::postParameters() );

        wp_send_json_success( array( 'html' => self::renderTemplate( '_list', $data, false ), 'service_id' => $service->getId() ) );
    }

    /**
     * 'Safely' remove services (report if there are future appointments)
     */
    public static function removeServices()
    {
        $service_ids = self::parameter( 'service_ids', array() );
        if ( self::parameter( 'force_delete', false ) ) {
            if ( is_array( $service_ids ) && ! empty ( $service_ids ) ) {
                foreach ( $service_ids as $service_id ) {
                    Proxy\Shared::serviceDeleted( $service_id );
                }
                Lib\Entities\Service::query( 's' )->delete()->whereIn( 's.id', $service_ids )->execute();
            }
        } else {
            /** @var Lib\Entities\Appointment $appointment */
            $appointment = Lib\Entities\Appointment::query( 'a' )
                ->whereIn( 'a.service_id', $service_ids )
                ->whereGt( 'a.start_date', current_time( 'mysql' ) )
                ->sortBy( 'a.start_date' )
                ->order( 'DESC' )
                ->limit( '1' )
                ->findOne();

            if ( $appointment ) {
                $last_month = date_create( $appointment->getStartDate() )->modify( 'last day of' )->format( 'Y-m-d' );
                $action = 'show_modal';
                $filter_url = sprintf( '%s#service=%d&range=%s-%s',
                    Lib\Utils\Common::escAdminUrl( \Bookly\Backend\Modules\Appointments\Page::pageSlug() ),
                    $appointment->getServiceId(),
                    date_create( current_time( 'mysql' ) )->format( 'Y-m-d' ),
                    $last_month );
                wp_send_json_error( compact( 'action', 'filter_url' ) );
            } else {
                $action = 'confirm';
                wp_send_json_error( compact( 'action' ) );
            }
        }

        wp_send_json_success();
    }

    /**
     * Update service parameters and assign staff
     */
    public static function updateService()
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $form = new Forms\Service();
        $form->bind( self::postParameters() );
        $service = $form->save();

        $staff_ids = self::parameter( 'staff_ids', array() );
        if ( empty ( $staff_ids ) ) {
            Lib\Entities\StaffService::query()->delete()->where( 'service_id', $service->getId() )->execute();
        } else {
            Lib\Entities\StaffService::query()->delete()->where( 'service_id', $service->getId() )->whereNotIn( 'staff_id', $staff_ids )->execute();
            if ( $service->getType() == Lib\Entities\Service::TYPE_SIMPLE ) {
                if ( self::parameter( 'update_staff', false ) ) {
                    $data = array(
                        'price'        => self::parameter( 'price' ),
                        'capacity_min' => $service->getCapacityMin(),
                        'capacity_max' => $service->getCapacityMax(),
                    );
                    $wpdb->update(
                        Lib\Entities\StaffService::getTableName(),
                        $data,
                        array( 'service_id' => self::parameter( 'id' ) )
                    );
                }
                // Create records for newly linked staff.
                $existing_staff_ids = array();
                $res = Lib\Entities\StaffService::query()
                    ->select( 'staff_id' )
                    ->where( 'service_id', $service->getId() )
                    ->fetchArray();
                foreach ( $res as $staff ) {
                    $existing_staff_ids[] = $staff['staff_id'];
                }
                foreach ( $staff_ids as $staff_id ) {
                    if ( ! in_array( $staff_id, $existing_staff_ids ) ) {
                        $staff_service = new Lib\Entities\StaffService();
                        $staff_service->setStaffId( $staff_id )
                            ->setServiceId( $service->getId() )
                            ->setPrice( $service->getPrice() )
                            ->setCapacityMin( $service->getCapacityMin() )
                            ->setCapacityMax( $service->getCapacityMax() )
                            ->save();
                    }
                }
            }
        }

        // Update services in addons.
        $alert = Proxy\Shared::updateService( array( 'success' => array( __( 'Settings saved.', 'bookly' ) ) ), $service, self::postParameters() );

        $price = Lib\Utils\Price::format( $service->getPrice() );
        $nice_duration = Lib\Utils\DateTime::secondsToInterval( $service->getDuration() );
        $title = $service->getTitle();
        $colors = array_fill( 0, 3, $service->getColor() );

        wp_send_json_success( Proxy\Shared::prepareUpdateServiceResponse( compact( 'title', 'price', 'colors', 'nice_duration', 'alert' ), $service, self::postParameters() ) );
    }
}