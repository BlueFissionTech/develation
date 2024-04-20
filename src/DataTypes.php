<?php

namespace BlueFission;

enum DataTypes: string
{
	case GENERIC = 'generic';
	case STRING = 'string';
	case NUMBER = 'number';
	case DOUBLE = 'double';
	case FLOAT = 'float';
	case INTEGER = 'integer';
	case BOOLEAN = 'boolean';
	case DATETIME = 'datetime';
	case ARRAY = 'array';
	case OBJECT = 'object';
	case RESOURCE = 'resource';
	case NULL = 'null';
	case SCALAR = 'scalar';
	case CALLBACK = 'callback';
}