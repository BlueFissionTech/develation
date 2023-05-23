<?php
namespace BlueFission;

class DevValueFactory {
	static const GENERIC = 'generic';
	static const STRING = 'string';
	static const NUMBER = 'number';
	static const BOOLEAN = 'boolean';
	static const DATETIME = 'datetime';
	static const ARRAY = 'array';
	static const OBJECT = 'object';

	static function make( $type = null, $args = null ): IDevValue
	{
		switch (strtolower($type)) {
			case self::STRING:
				$class = '\BlueFission\DevString';
				break;
			case self::NUMBER:
				$class = '\BlueFission\DevNumber';
				break;
			case self::BOOLEAN:
				$class = '\BlueFission\DevBoolean';
				break;
			case self::DATETIME:
				$class = '\BlueFission\DevDateTime';
				break;
			case self::ARRAY:
				$class = '\BlueFission\DevArray';
				break;
			case self::OBJECT:
				$class = '\BlueFission\DevObject';
				break;
			default:
			case self::GENERIC:
				$class = '\BlueFission\DevValue';
				break;
		}

		return new $class($args);
	}
}