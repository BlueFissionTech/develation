<?php

namespace BlueFission\Parsing\Preparers;

use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

abstract class BasePreparer implements IElementPreparer
{
	protected $data = null;
	protected $context = null;
	protected $supported = [];

	public function __construct($data = null)
	{
		if ($data) {
			$this->data = Dev::apply('_in', $data);
		}
	}

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

		return Dev::apply('_out', $passes);
	}

	public function setsSupported(array $supports):void
	{
		$this->supported = $supports;
	}

	public function setContext($context = null): void
	{
		$this->context = Dev::apply('_in', $context);
	}

	public function getData(): mixed
	{
		return Dev::apply('_out', $this->data);
	}

	public function getContext(): mixed
	{
		return Dev::apply('_out', $this->context);
	}

	abstract public function prepare(Element $element): void;
}
