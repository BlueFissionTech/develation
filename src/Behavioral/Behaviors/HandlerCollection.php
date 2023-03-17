<?php
namespace BlueFission\Behavioral\Behaviors;

use BlueFission\Collections\Collection;
use BlueFission\Exceptions\NotImplementedException;

/**
 * Class HandlerCollection
 *
 * @package BlueFission\Behavioral\Behaviors
 */
class HandlerCollection extends Collection
{
	/**
	 * Add a handler to the collection with optional priority value
	 *
	 * @param object $handler The handler to add
	 * @param int $priority The priority value for the handler
	 * @return void
	 */
	public function add($handler, $priority = null)
	{
		$handler->priority($priority);
		$this->_value->append($handler);
		$this->prioritize();
	}

	/**
	 * Check if the collection has a handler with the given name
	 *
	 * @param string $behaviorName The name of the behavior to check for
	 * @return bool
	 */
	public function has( $behaviorName )
	{
		foreach ($this->_value as $c)
		{
			if ($c->name() == $behaviorName)
				return true;
		}
		return false;
	}

	/**
	 * Get an array of handlers with the given behavior name
	 *
	 * @param string $behaviorName The name of the behavior to get handlers for
	 * @return array
	 */
	public function get( $behaviorName )
	{
		$handlers = array();
		foreach ($this->_value as $c)
		{
			if ($c->name() == $behaviorName)
				$handlers[] = $c;
		}
		return $handlers;
	}

	/**
	 * Raise a behavior event and trigger the associated handlers
	 *
	 * @param object $behavior The behavior object to raise
	 * @param object $sender The sender object of the behavior event
	 * @param array $args An array of arguments for the behavior event
	 * @return void
	 */
	public function raise($behavior, $sender, $args)
	{
		if (is_string($behavior))
			$behavior = new Behavior($behavior);

		$behavior->_target = $behavior->_target ? $behavior->_target : $sender;

		foreach ($this->_value as $c)
		{
			if ($c->name() == $behavior->name())
			{
				$c->raise($behavior, $args);
			}
		}
	}

	/**
	 * Sort the collection of handlers based on priority value
	 *
	 * @return int
	 */
	private function prioritize()
	{
		$compare = $this->_value->uasort( function( $a, $b ) {
			if ( !($a instanceof Handler) || !($b instanceof Handler ) )
				return -1;

			if ($a->priority() == $b->priority()) 
			{
				return 0;
			}
		
			return ($a->priority() < $b->priority()) ? -1 : 1;
		});

		return $compare;
	}
}