<?php
namespace Bookly\Backend\Modules\Shop;

use Bookly\Lib;

/**
 * Class Ajax
 * @package Bookly\Backend\Modules\Shop
 */
class Ajax extends Lib\Base\Ajax
{
    /**
     * Get data for shop page.
     */
    public static function getShopData()
    {
        $response = array();
        $order    = self::parameter( 'sort' );
        if ( ! Lib\Entities\Shop::query()->count() ) {
            Lib\API::updateInfo();
            $order = 'date';
        }
        $query = Lib\Entities\Shop::query();
        switch ( $order ) {
            case 'sales':
                $query = $query
                    ->sortBy( 'sales' )
                    ->order( 'DESC' );
                break;
            case 'rating':
                $query = $query
                    ->sortBy( 'rating' )
                    ->order( 'DESC' );
                break;
            case 'date':
                $query = $query
                    ->sortBy( 'published' )
                    ->order( 'DESC' );
                break;
            case 'price_low':
                $query = $query
                    ->sortBy( 'price' );
                break;
            case 'price_high':
                $query = $query
                    ->sortBy( 'price' )
                    ->order( 'DESC' );
                break;
            default:
                $query = $query
                    ->sortBy( 'type DESC, created' )
                    ->order( 'DESC' );
                break;
        }
        $shop = $query->fetchArray();

        // Get a list of installed plugins
        $plugins_installed = array_keys( apply_filters( 'bookly_plugins', array() ) );
        foreach ( glob( Lib\Plugin::getDirectory() . '/../bookly-addon-*', GLOB_ONLYDIR ) as $path ) {
            $plugins_installed[] = basename( $path );
        }

        // Build a list of plugins for a shop page
        $response['shop'] = array();
        foreach ( $shop as $plugin ) {
            $installed          = in_array( $plugin['slug'], $plugins_installed );
            $response['shop'][] = array(
                'plugin_class' => $plugin['type'] == 'bundle' ? 'bookly-shop-bundle' : '',
                'title'        => $plugin['title'],
                'description'  => $plugin['description'],
                'icon'         => '<img src="' . $plugin['icon'] . '"/>',
                'new'          => ( $plugin['seen'] == 0 || ( strtotime( $plugin['published'] ) > strtotime( '-2 weeks' ) ) ) ? __( 'New', 'bookly' ) : '',
                'price'        => '$' . $plugin['price'],
                'sales'        => sprintf( _n( '%d sale', '%d sales', $plugin['sales'], 'bookly' ), $plugin['sales'] ),
                'rating'       => $plugin['rating'],
                'reviews'      => sprintf( _n( '%d review', '%d reviews', $plugin['reviews'], 'bookly' ), $plugin['reviews'] ),
                'url_class'    => $installed ? 'btn-default' : 'btn-success',
                'url_text'     => $installed ? __( 'Installed', 'bookly' ) : __( 'Get it!', 'bookly' ),
                'url'          => $plugin['url'] . '?ref=ladela',
            );
        }

        // Mark all plugins as seen
        Lib\Entities\Shop::query()->update()->set( 'seen', 1 )->execute();

        return wp_send_json_success( $response );
    }
}