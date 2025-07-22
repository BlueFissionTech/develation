<?php

namespace BlueFission\Parsing\Preparers;

use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\Parsing\Element;

abstract class BasePreparer implements IElementPreparer
{
	protected $owner = null;
	protected $supported = [];

	public function supports (Element $element): bool
	{
		if (empty($this->supported)) {
			return true;
		}

		$passes = false;

		foreach ($this->supported as $type) {
			if ($element->getTag() == $type || is_a($element, $type) || is_subclass_of($element, $type)) {
				$passes = true;
				break;
			}
		}

		return $passes;
	}

	public function setsSupported(array $supports):void
	{
		$this->supported = $supports;
	}

	public function ready($data = null): void
	{
		$this->owner = $data;
	}

	abstract public function prepare(Element $element): void;
}