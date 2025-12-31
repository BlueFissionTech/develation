<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IExecutableElement;

class LetElement extends Element implements IExecutableElement
{
    public function execute(): mixed
    {
        if (empty($this->attributes)) return null;

        foreach ($this->attributes as $key=>$value) {
        	$type = '';

	        if (strpos($key, ':')) {
	            [$var, $type] = explode(':', $key, 2);
	        } else {
	            $var = $key;
	            $type = 'string';
	        }

        	$parsed = $this->resolveValue($value, $type);
	        $this->block->setVar($var, $parsed);
        }

        return null;
    }

    public function render(): string
    {
    	return '';
    }

    public function getDescription(): string
    {
        $name = array_key_first($this->attributes);
        $value = $this->attributes[$name];

        $descriptionString = sprintf('Declare a new variable named `%s` with the value `%s`', $name, $value);

        $this->description = $descriptionString;

        return $this->description;
    }
}