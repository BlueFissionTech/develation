<?php
namespace BlueFission\Behavioral\Behaviors;

use BlueFission\Collections\Collection;
use BlueFission\Collections\ICollection;

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
	 * @param object $_handler The handler to add
	 * @param int $_priority The priority value for the handler
	 * @return ICollection
	 */
	public function add($_handler, $_priority = null): ICollection
	{
		$_handler->priority($_priority);
		$this->_value->append($_handler);
		$this->prioritize();

		return $this;
	}

	/**
	 * Check if the collection has a handler with the given name
	 *
	 * @param string $_behaviorName The name of the behavior to check for
	 * @return bool
	 */
	public function has( $_behaviorName )
	{
		foreach ($this->_value as $c)
		{
			if ($c->name() == $_behaviorName)
				return true;
		}
		return false;
	}

	/**
	 * Get an array of handlers with the given behavior name
	 *
	 * @param string $_behaviorName The name of the behavior to get handlers for
	 * @return array
	 */
	public function get( $_behaviorName )
	{
		$_handlers = [];
		foreach ($this->_value as $c)
		{
			if ($c->name() == $_behaviorName)
				$_handlers[] = $c;
		}
		return $_handlers;
	}

	/**
	 * Raise a behavior event and trigger the associated handlers
	 *
	 * @param object $_behavior The behavior object to raise
	 * @param object $_sender The sender object of the behavior event
	 * @param array $_args An array of arguments for the behavior event
	 * @return ICollection
	 */
	public function raise($_behavior, $_sender, $_args): ICollection
	{
		if (is_string($_behavior))
			$_behavior = new Behavior($_behavior);

		$_behavior->target = $_behavior->target ?? $_sender;

		foreach ($this->_value as $c)
		{
			if ($c->name() == $_behavior->name())
			{
				$c->raise($_behavior, $_args);
			}
		}

		return $this;
	}

	/**
	 * Sort the collection of handlers based on priority value
	 *
	 * @return int
	 */
	private function prioritize()
	{
		$_compare = $this->_value->uasort( function( $_a, $_b ) {
			if ( !($_a instanceof Handler) || !($_b instanceof Handler ) )
				return -1;

			if ($_a->priority() == $_b->priority()) 
			{
				return 0;
			}
		
			return ($_a->priority() < $_b->priority()) ? -1 : 1;
		});

		return $_compare;
	}
}