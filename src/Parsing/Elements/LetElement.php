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
}