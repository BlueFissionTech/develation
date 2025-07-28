<?php

namespace BlueFission\Behavioral\Behaviors;

use BlueFission\Behavioral\Behaves;
use BlueFission\Behavioral\Configurable;
use BlueFission\Obj;

class Meta {
	public function __construct(
		private $when = null, // Which behavior does this relate to?
		private $info = '', // Statuses or a message about the context
		private $data = [], // Objects or arrays related to the context
		private $src = null // The object this behavior is related to
	)
	{
		$this->when = is_string($when) ? new Behavior($when) : $when;
		if (!$when && $src instanceof Behaves) {
			$this->when = $src->is() ?? $src->just();
		}

		$this->data = is_array($data) ? $data : [$data];
		if (!$data && $src instanceof Obj) {
			$this->data = $src->data();
		}

		if (!$info && $src instanceof Configurable) {
			$this->info = $src->status();
		}
	}

	public function __get($name)
	{
		if ( isset($this->$name) ) {
			return $this->$name;
		}
	}
}