<?php

namespace BlueFission\Parsing\Preparers;

use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class HierarchyPreparer extends BasePreparer
{
	public function prepare(Element $element): void
	{
		Dev::do('_before', [$element, $this->context]);
		if ( !$this->context ) {
			return;
		}

		$element->setParent($this->context);
		Dev::do('_after', [$element, $this->context]);
	}
}
