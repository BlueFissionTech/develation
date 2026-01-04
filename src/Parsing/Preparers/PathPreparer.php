<?php

namespace BlueFission\Parsing\Preparers;

use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class PathPreparer extends BasePreparer
{
	public function prepare(Element $element): void
	{
		Dev::do('_before', [$element, $this->context]);
		if ( !$this->context ) {
			return;
		}

		$element->setIncludePaths($this->context->getIncludePaths());
		Dev::do('_after', [$element, $this->context]);
	}
}
