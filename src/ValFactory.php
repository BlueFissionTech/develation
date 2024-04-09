<?php
namespace BlueFission;

class ValFactory {
	static const GENERIC = 'generic';
	static const STRING = 'string';
	static const NUMBER = 'number';
	static const BOOLEAN = 'boolean';
	static const DATETIME = 'datetime';
	static const ARRAY = 'array';
	static const OBJECT = 'object';

	static function make( $type = null, $args = null ): IVal
	{
		switch (strtolower($type)) {
			case self::STRING:
				$class = '\BlueFission\Str';
				break;
			case self::NUMBER:
				$class = '\BlueFission\Num';
				break;
			case self::BOOLEAN:
				$class = '\BlueFission\Flag';
				break;
			case self::DATETIME:
				$class = '\BlueFission\Date';
				break;
			case self::ARRAY:
				$class = '\BlueFission\Arr';
				break;
			case self::OBJECT:
				$class = '\BlueFission\Obj';
				break;
			default:
			case self::GENERIC:
				$class = '\BlueFission\Val';
				break;
		}

		return new $class($args);
	}
}