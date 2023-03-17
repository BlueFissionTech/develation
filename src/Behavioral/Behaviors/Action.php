<?php
namespace BlueFission\Behavioral\Behaviors;

/**
 * Class Action
 * 
 * Represents a behavior that performs an action in response to an event
 */
class Action extends Behavior
{
	/**
	 * Constant value representing activation of the behavior
	 * 
	 * @var string
	 */
	const ACTIVATE = 'DoActivate';

	/**
	 * Constant value representing an update of the behavior
	 * 
	 * @var string
	 */
	const UPDATE = 'DoUpdate';

	/**
	 * Constructor
	 * 
	 * @param string $name  The name of the action
	 */
	public function __construct( $name )
	{
		parent::__construct( $name, 0, false, true );
	}
}