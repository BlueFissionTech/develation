<?php
namespace BlueFission\Behavioral\Behaviors;

/**
 * Class State
 *
 * A class that represents a state behavior in a behavioral model.
 *
 * @package BlueFission\Behavioral\Behaviors
 */
class State extends Behavior
{
	const DRAFT = 'IsDraft';
	const DONE = 'IsDone';
	const NORMAL = 'IsNormal';
	const READONLY = 'IsReadonly';
	const BUSY = 'IsBusy';

	/**
	 * State constructor.
	 *
	 * @param string $name The name of the state behavior.
	 */
	public function __construct( $name )
	{
		parent::__construct( $name, 0, true, true );
	}
}
