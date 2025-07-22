<?php

namespace BlueFission\Parsing\Preparers;

use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\Parsing\Element;

class VariablePreparer extends BasePreparer
{
	public function prepare(Element $element): void
	{
		if ( !$this->context ) {
			return;
		}

        if (!$element->isClosed()) {
        	return;
        }
		
		if (is_object($this->context) && property_exists($this->context, 'vars') && is_iterable($this->context->vars)) {
			foreach ($this->context->vars as $name => $value) {
            	$element->setScopeVariable($name, $value);
        	}
		}
	}
}