<?php
namespace BlueFission;

class ValFactory {
	static function make( $type = null ): IVal
	{
		$args = func_get_args();
		array_shift($args);

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
			default:
			case DataTypes::GENERIC->value:
				$class = '\BlueFission\Val';
				break;
		}

		return new $class(...$args);
	}
}