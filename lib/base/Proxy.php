<?php
namespace Bookly\Lib\Base;

/**
 * Class Proxy
 * @package Bookly\Lib\Base
 */
abstract class Proxy extends Component
{
    /**
     * Register proxy methods.
     */
    public static function init()
    {
        $called_class      = get_called_class();
        $parent_class_name = static::reflection()->getParentClass()->getName();

        foreach ( static::reflection()->getMethods( \ReflectionMethod::IS_STATIC | \ReflectionMethod::IS_PUBLIC ) as $method ) {
            if ( $method->class != $called_class ) {
                // Stop if parent class reached.
                break;
            }

            $action   = $parent_class_name . '::' . $method->name;
            $function = function () use ( $method ) {
                $args = func_get_args();
                $res  = $method->invokeArgs( null, $args );

                return $res === null ? $args[0] : $res;
            };

            add_filter( $action, $function, 10, $method->getNumberOfParameters() ?: 1 );
        }
    }

    /**
     * Invoke proxy method.
     *
     * @param string $method
     * @param mixed $args
     * @return mixed
     */
    public static function __callStatic( $method, $args )
    {
        $action = get_called_class() . '::' . $method;

        if ( has_filter( $action ) ) {
            return apply_filters_ref_array( $action, empty ( $args ) ? array( null ) : $args );
        }

        // Return null for void methods or methods with "get" and "find" prefixes.
        return empty ( $args ) || preg_match( '/^(?:get|find)/', $method )
            ? null
            : $args[0];
    }

    /**
     * @inheritdoc
     */
    protected static function directory()
    {
        return dirname( parent::directory() );
    }
}