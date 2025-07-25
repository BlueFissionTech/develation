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

        if ($element->isClosed()) {
        	return;
        }

		if (is_object($this->context) && $this->context->getAllVariables()) {
			foreach ($this->context->getAllVariables() as $name => $value) {
            	$element->setScopeVariable($name, $value);
        	}
		}
	}
}