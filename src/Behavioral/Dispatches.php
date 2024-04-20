<?php
namespace BlueFission\Behavioral;

use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\Behaviors\Handler;
use BlueFission\Behavioral\Behaviors\HandlerCollection;
use BlueFission\Behavioral\Behaviors\BehaviorCollection;
use InvalidArgumentException;

/**
 * Trait Dispatches is used to dispatch events and handlers 
 * To be paired with IDispatcher
 */
trait Dispatches {
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
	 * Constructor of the Dispatches class
	 *
	 * @param HandlerCollection $handlers Optional collection of handlers to add to the Dispatches object
	 */
	public function __construct( HandlerCollection $handlers = null ) {
		$this->_behaviors = new BehaviorCollection();

		if ($handlers)
			$this->_handlers = $handlers;
		else
			$this->_handlers = new HandlerCollection();

		$this->trigger(Event::LOAD);
	}

	/**
	 * Destructor of the Dispatches class
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
	public function behavior( $behavior, $callback = null ): IDispatcher 
	{
		if ( is_string($behavior) && Val::isNotEmpty($behavior))
			$behavior = new Behavior($behavior);

		if ( !($behavior instanceof Behavior) ) {
			$this->trigger( Event::EXCEPTION );
			throw new InvalidArgumentException("Invalid Behavior Type");
		}
			
		$this->_behaviors->add( $behavior );

		if ( $callback ) {
			try {
				$this->handler( new Handler( $behavior, $callback ) );
			} catch ( InvalidArgumentException $e ) {
				$this->trigger( Event::ERROR );
				error_log( $e->getMessage() );
			}
		}

		return $this;
	}

	/**
	 * When, an alias for behavior, adds a behavior to the behavior collection and creates a callback to trigger the behavior if specified.
	 *
	 * @param Behavior|string $behavior The behavior to be added. Can be a string or an instance of Behavior.
	 * @param callable $callback The callback to trigger the behavior if specified to add as a handler for the behavior.
	 *
	 * @throws InvalidArgumentException if the behavior is not a string or an instance of Behavior.
	 */
	public function when( $behavior, callable $callback ): IDispatcher
	{
		return $this->behavior($behavior, $callback);
	}

	/**
	 * Repeats another Dispatcher's selected behaviors
	 *
	 * @param IDispatcher $otherObject The other object to repeat the behaviors from
	 * @param mixed $behaviors The behaviors to repeat
	 */
	public function echo( IDispatcher $otherObject, $behaviors ): IDispatcher
	{
        if (!is_array($behaviors)) {
            $behaviors = [$behaviors];
        }

        foreach ($behaviors as $behavior) {
            $otherObject->when($behavior, function() use ($behavior) {
                $this->dispatch($behavior);
            });
        }

        return $this;
    }
	
	/**
	 * Adds a handler to the handler collection.
	 * 
	 * @param Handler $handler The handler to be added.
	 */
	public function handler($handler): IDispatcher
	{
		if ($this->_behaviors->has($handler->name())) {
			$this->_handlers->add($handler);
		}

		return $this;
	}
	
	/**
	 * Triggers a behavior by calling the trigger method with the behavior and arguments specified.
	 * 
	 * @param mixed $behavior The behavior to be triggered. Can be a string or an instance of Behavior.
	 * @param mixed|null $args The arguments to pass to the trigger method.
	 */
	public function dispatch( $behavior, $args = null ): IDispatcher
	{
		if (is_string($behavior))
			$behavior = new Behavior($behavior);

			if (!is_array($args) && !($args instanceof Meta)) {
				$args = [$args];
			}

		return $this->trigger( $behavior, $args );
	}
	
	/**
	 * Triggers a behavior by raising the behavior in the handler collection.
	 * 
	 * @param Behavior $behavior The behavior to be triggered.
	 * @param mixed|null $args The arguments to pass to the handler.
	 */
	protected function trigger($behaviors, $args = null): IDispatcher
	{
		if ( !is_array($behaviors) ) {
			$behaviors = [$behaviors];
		}

		foreach ( $behaviors as $behavior ) {
			if ( $this->_handlers != null ) {
				$this->_handlers->raise($behavior, $this, $args);
			}
		}

		return $this;
	}
}