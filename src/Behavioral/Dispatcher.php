<?php
namespace BlueFission\Behavioral;

use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Handler;
use BlueFission\Behavioral\Behaviors\HandlerCollection;
use BlueFission\Behavioral\Behaviors\BehaviorCollection;
use InvalidArgumentException;

/**
 * Class Dispatcher is used to dispatch events and handlers 
 */
class Dispatcher {
	/**
	 * Holds a collection of behaviors
	 *
	 * @var BehaviorCollection 
	 */
	protected $_behaviors;
	
	/**
	 * Holds a collection of handlers
	 *
	 * @var HandlerCollection 
	 */
	protected $_handlers;
	
	/**
	 * Constructor of the Dispatcher class
	 *
	 * @param HandlerCollection $handlers Optional collection of handlers to add to the Dispatcher object
	 */
	public function __construct( HandlerCollection $handlers = null ) {
		$this->_behaviors = new BehaviorCollection();

		if ($handlers)
			$this->_handlers = $handlers;
		else
			$this->_handlers = new HandlerCollection();

		$this->init();
		$this->trigger(Event::LOAD);
	}

	/**
	 * Destructor of the Dispatcher class
	 */
	public function __destruct() {
		if ( $this->_behaviors ) {
			$this->trigger(Event::UNLOAD);
		}
	}

	/**
	 * Adds a behavior to the behavior collection and creates a callback to trigger the behavior if specified.
	 * 
	 * @param Behavior|string $behavior The behavior to be added. Can be a string or an instance of Behavior.
	 * @param callable|null $callback The callback to trigger the behavior if specified to add as a handler for the behavior.
	 * 
	 * @throws InvalidArgumentException if the behavior is not a string or an instance of Behavior.
	 */
	public function behavior( $behavior, $callback = null ) {
		if ( is_string($behavior) && !empty($behavior))
			$behavior = new Behavior($behavior);

		if ( !($behavior instanceof Behavior) ) {
			throw new InvalidArgumentException("Invalid Behavior Type");
		}
			
		$this->_behaviors->add( $behavior );

		if ( $callback ) {
			try {
				$this->handler( new Handler( $behavior, $callback ) );
			} catch ( InvalidArgumentException $e ) {
				error_log( $e->getMessage() );
			}
		}
	}
	
	/**
	 * Adds a handler to the handler collection.
	 * 
	 * @param Handler $handler The handler to be added.
	 */
	public function handler($handler) {
		if ($this->_behaviors->has($handler->name())) {
			$this->_handlers->add($handler);
		}
	}
	
	/**
	 * Triggers a behavior by calling the trigger method with the behavior and arguments specified.
	 * 
	 * @param mixed $behavior The behavior to be triggered. Can be a string or an instance of Behavior.
	 * @param mixed|null $args The arguments to pass to the trigger method.
	 */
	public function dispatch( $behavior, $args = null ) {
		if (is_string($behavior))
			$behavior = new Behavior($behavior);

			if (!is_array($args)) {
				$args = array($args);
			}

		$this->trigger( $behavior, $args);
	}
	
	/**
	 * Triggers a behavior by raising the behavior in the handler collection.
	 * 
	 * @param Behavior $behavior The behavior to be triggered.
	 * @param mixed|null $args The arguments to pass to the handler.
	 */
	protected function trigger($behavior, $args = null) {
		$this->_handlers->raise($behavior, $this, $args);
	}
	
	/**
	 * Initializes the class by adding the load and unload events to the behavior collection.
	 */
	protected function init() {
		$this->behavior(new Event(Event::LOAD));
		$this->behavior(new Event(Event::UNLOAD));
	}

}