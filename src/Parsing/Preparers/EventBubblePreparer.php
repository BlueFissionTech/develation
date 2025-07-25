<?php

namespace BlueFission\Parsing\Preparers;

use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Parsing\Element;

class EventBubblePreparer extends BasePreparer
{
	public function prepare(Element $element): void
	{
		if ( !$this->context ) {
			return;
		}

		$this->context->echo($element, [Event::STARTED, Event::SENT, Event::ERROR, Event::RECEIVED, Event::COMPLETE]);
	}
}