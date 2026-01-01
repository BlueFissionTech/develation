<?php

namespace BlueFission\Parsing\Preparers;

use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class EventBubblePreparer extends BasePreparer
{
	public function prepare(Element $element): void
	{
		Dev::do('_before', [$element, $this->context]);
		if ( !$this->context ) {
			return;
		}

		$this->context->echo($element, [Event::STARTED, Event::SENT, Event::ERROR, Event::RECEIVED, Event::COMPLETE]);
		Dev::do('_after', [$element, $this->context]);
	}
}
