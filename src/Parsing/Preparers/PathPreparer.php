<?php

namespace BlueFission\Parsing\Preparers;

use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\Parsing\Element;

class PathPreparer extends BasePreparer
{
	public function prepare(Element $element): void
	{
		if ( !$this->context ) {
			return;
		}

		$element->setIncludePaths($this->context->getIncludePaths());
	}
}