<?php

namespace BlueFission\Parsing\Preparers;

use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\Parsing\Element;

abstract class BasePreparer implements IElementPreparer
{
	protected $data = null;
	protected $context = null;
	protected $supported = [];

	public function __construct($data = null)
	{
		if ($data) {
			$this->data = $data;
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

		return $passes;
	}

	public function setsSupported(array $supports):void
	{
		$this->supported = $supports;
	}

	public function setContext($context = null): void
	{
		$this->context = $context;
	}

	public function getData(): mixed
	{
		return $this->data;
	}

	public function getContext(): mixed
	{
		return $this->context;
	}

	abstract public function prepare(Element $element): void;
}