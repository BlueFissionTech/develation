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
	 * @param Behavior $behavior
	 * @param callable $callback
	 * @param int $priority
	 */
	public function __construct(Behavior $behavior, $callback, $priority = 0) {
		$this->_behavior = $behavior;
		$this->_callback = $this->prepare($callback);
		$this->_priority = (int)$priority;
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
	 * @param Behavior $behavior
	 * @param mixed $args
	 */
	public function raise(Behavior $behavior, $args) {
		if ($this->_callback)
		{

			$args = $args ?? null;
						
			if (is_callable($this->_callback)) {
				call_user_func_array($this->_callback, [$behavior, $args]);
			}
		}
	}

	/**
	 * Prepares the callback function to be used as a callable.
	 *
	 * @param callable $callback
	 * @return callable
	 */
	private function prepare($callback) {
		$process = '';
		if ( is_array( $callback ) ) {
			if ( count($callback) < 2 ) {
				$process = $callback[0];
			} else {
				$process = $callback;
			}
		} elseif ( is_string( $callback ) ) {
			$process = $callback;
			if ($pos = strpos($process, '('))
				$process = substr($process, 0, $pos);
		} else {
			$process = $callback;
		}
		
		if (!is_callable($process, true) ) {
			throw new \InvalidArgumentException('Handler is not callable');
		}

		return $process;
	}

	/**
	 * Gets or sets the priority of the handler.
	 *
	 * @param int|null $int
	 * @return int
	 */
	public function priority( $int = null ) {
		if ( $int )
			$this->_priority = (int)$int;

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