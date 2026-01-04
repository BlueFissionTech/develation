<?php

namespace BlueFission\Parsing\Preparers;

use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class VariablePreparer extends BasePreparer
{
	public function prepare(Element $element): void
	{
		Dev::do('_before', [$element, $this->context]);
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
		Dev::do('_after', [$element, $this->context]);
	}
}
