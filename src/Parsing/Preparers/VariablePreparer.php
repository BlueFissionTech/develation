<?php

namespace BlueFission\Parsing\Preparers;

use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\Parsing\Element;

class VariablePreparer extends BasePreparer
{
	public function prepare(Element $element): void
	{
		if ( !$this->data ) {
			return;
		}

        if (!$element->isClosed()) {
        	return;
        }
		
		foreach($this->data->vars as $name => $value) {
            $element->setScopeVariable($name, $value);
        }
	}
}