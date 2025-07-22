<?php

namespace BlueFission\Parsing\Preparers;

use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\Parsing\Element;

abstract class BasePreparer implements IElementPreparer
{
	protected $data = null;
	protected $supported = [];

	public function __construct($data = null)
	{
		if ($data) {
			$this->ready($data);
		}
	}

	public function supports (Element|array $elements): bool
	{
		if (empty($this->supported)) {
			return true;
		}

		$elements = is_array($elements) ? $elements : [$elements];
		$passes = false;

		foreach ($elements as $element) {
			foreach ($this->supported as $type) {
				if ($element->getTag() == $type || is_a($element, $type) || is_subclass_of($element, $type)) {
					$passes = true;
					break 2;
				}
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
		$this->data = $data;
	}

	public function getData(): mixed
	{
		return $this->data;
	}

	abstract public function prepare(Element $element): void;
}