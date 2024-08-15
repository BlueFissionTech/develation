<?php
namespace BlueFission\Behavioral\Behaviors;

/**
 * Class Handler
 * 
 * The class is responsible for handling callbacks for behaviors.
 */
class Handler
{
	/**
	 * @var Behavior
	 */
	private $_behavior;

	/**
	 * @var callable
	 */
	private $_callback;

	/**
	 * @var int
	 */
	private $_priority;

	/**
	 * Handler constructor.
	 *
	 * @param Behavior $_behavior
	 * @param callable $_callback
	 * @param int $_priority
	 */
	public function __construct(Behavior $_behavior, $_callback, $_priority = 0) {
		$this->_behavior = $_behavior;
		$this->_callback = $this->prepare($_callback);
		$this->_priority = (int)$_priority;
	}

	/**
	 * Returns the name of the behavior being handled.
	 *
	 * @return string
	 */
	public function name() {
		return $this->_behavior->name();
	}

	/**
	 * Raises the behavior and calls the handler callback function.
	 *
	 * @param Behavior $_behavior
	 * @param mixed $_args
	 */
	public function raise(Behavior $_behavior, $_args) {
		if ($this->_callback)
		{

			$_args = $_args ?? null;
						
			if (is_callable($this->_callback)) {
				call_user_func_array($this->_callback, [$_behavior, $_args]);
			}
		}
	}

	/**
	 * Prepares the callback function to be used as a callable.
	 *
	 * @param callable $_callback
	 * @return callable
	 */
	private function prepare($_callback) {
		$process = '';
		if ( is_array( $_callback ) ) {
			if ( count($_callback) < 2 ) {
				$process = $_callback[0];
			} else {
				$process = $_callback;
			}
		} elseif ( is_string( $_callback ) ) {
			$process = $_callback;
			if ($pos = strpos($process, '('))
				$process = substr($process, 0, $pos);
		} else {
			$process = $_callback;
		}
		
		if (!is_callable($process, true) ) {
			throw new \InvalidArgumentException('Handler is not callable');
		}

		return $process;
	}

	/**
	 * Gets or sets the priority of the handler.
	 *
	 * @param int|null $_int
	 * @return int
	 */
	public function priority( $_int = null ) {
		if ( $_int )
			$this->_priority = (int)$_int;

		return $this->_priority;
	}

	/**
	 * Returns the callback for the handler
	 *
	 * @return mixed Callback for the handler
	 */
	public function callback() {
		return $this->_callback;
	}
}