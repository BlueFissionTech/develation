<?php

namespace BlueFission\Parsing\Preparers;

use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\Parsing\Element;

class HierarchyPreparer extends BasePreparer
{
	public function prepare(Element $element): void
	{
		if ( !$this->data ) {
			return;
		}

		$element->setParent($this->data);
	}
}