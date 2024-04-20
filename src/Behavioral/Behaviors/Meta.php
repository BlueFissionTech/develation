<?php

namespace BlueFission\Behavioral\Behaviors;

class Meta {
	public function __construct(
		private $when = null, // Which behavior does this relate to?
		private $info = '', // Statuses or a message about the context
		private $data = [], // Objects or arrays related to the context
		private $src = null // The object this behavior is related to
	)
	{
		$this->when = is_string($when) ? new Behavior($when) : $when;
		$this->data = is_array($data) ? $data : [$data];
	}

	public function __get($name)
	{
		if ( isset($this->$name) ) {
			return $this->$name;
		}
	}
}