<?php

namespace BlueFission\Intelligence;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Dispatcher;

class Input extends Dispatcher {

	protected $_processor;

	public function __construct( $processor = null ) {
		parent::__construct();

		if ( !$processor ) {
			$processor = function( $data ) {
				return $data;
			};
		}
		$this->_processor = $processor;
	}

	public function setProcessor( $processorFunction ) {
		$this->_processor = $processorFunction;
	}

	public function scan( $data, $processor = null ) {
		if ( $processor ) {
			$this->_processor = $processor;
		}

		$data = call_user_func_array($this->_processor, array($data));

		$this->dispatch( Event::COMPLETE, $data );
	}

	public function dispatch( $behavior, $args = null) {

		if (is_string($behavior)) {
			$behavior = new Behavior($behavior);
			$behavior->_target = $this;
		}

		if ($behavior->_target == $this) {
			$behavior->_context = $args;
			$args = null;
		}

		parent::dispatch($behavior, $args);
	}

	protected function init() {
		parent::init();
	}
}