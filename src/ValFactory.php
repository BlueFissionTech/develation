<?php

namespace BlueFission;

use BlueFission\DataTypes;
use BlueFission\IVal;

/**
 * Factory class to instantiate IVal-based objects based on data type.
 */
class ValFactory
{
    /**
 * Factory method to create an IVal object of the specified type.
 *
 * @param string|DataTypes|null $type The data type to create (as string or DataTypes enum)
 * @param mixed ...$args Additional arguments passed to the constructor
 * @return IVal
 */
    public static function make($type = null): IVal
    {
        $args = func_get_args();
        array_shift($args);

        if ($type instanceof DataTypes) {
            $type = $type->value;
        }

        switch (strtolower($type)) {
            case DataTypes::STRING->value:
                $class = '\BlueFission\Str';
                break;
            case DataTypes::NUMBER->value:
            case DataTypes::DOUBLE->value:
            case DataTypes::FLOAT->value:
            case DataTypes::INTEGER->value:
                $class = '\BlueFission\Num';
                break;
            case DataTypes::BOOLEAN->value:
                $class = '\BlueFission\Flag';
                break;
            case DataTypes::DATETIME->value:
                $class = '\BlueFission\Date';
                break;
            case DataTypes::ARRAY->value:
                $class = '\BlueFission\Arr';
                break;
            case DataTypes::OBJECT->value:
                $class = '\BlueFission\Obj';
                break;
            case DataTypes::CALLABLE->value:
                $class = '\BlueFission\Func';
                break;
            default:
            case DataTypes::GENERIC->value:
                $class = '\BlueFission\Val';
                break;
        }

        return new $class(...$args);
    }
}
