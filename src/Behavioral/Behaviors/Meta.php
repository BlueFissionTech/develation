<?php

namespace BlueFission\Behavioral\Behaviors;

class Meta {
	public function __construct(
		private $_when = null, // Which behavior does this relate to?
		private $_info = '', // Statuses or a message about the context
		private $_data = [], // Objects or arrays related to the context
		private $src = null // The object this behavior is related to
	)
	{
		$this->when = is_string($_when) ? new Behavior($_when) : $_when;
		$this->data = is_array($_data) ? $_data : [$_data];
	}

	public function __get($_name)
	{
		if ( isset($this->$_name) ) {
			return $this->$_name;
		}
	}
}