<?php
namespace BlueFission\Behavioral;

use BlueFission\Arr;
use BlueFission\Val;
use BlueFission\Behavioral\Programmable;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Handler;

/**
 * Class StateMachine
 *
 * A trait that extends the Programmable trait and implements the concept of
 * state machines, allowing for allowed and denied behaviors.
 *
 * @package BlueFission\Behavioral
 */
trait StateMachine {
	use Programmable;

	/**
	 * Array that holds the names of behaviors that are denied in a certain state
	 *
	 * @var array
	 */
	protected $_deniedBehaviors = [];
	
	/**
	 * Array that holds the names of behaviors that are allowed in a certain state
	 *
	 * @var array
	 */
	protected $_allowedBehaviors = [];

	/**
	 * Checks if a behavior is denied in a state
	 *
	 * @param string $behaviorName The name of the behavior to check
	 *
	 * @return bool True if the behavior is denied, false otherwise
	 */
	private function behaviorIsDenied( $behaviorName ) {
		foreach ( $this->_state as $state => $args ) {
			if ( ( Val::is($this->_deniedBehaviors[$state]) && Arr::count($this->_deniedBehaviors[$state] > 0) ) &&
				in_array($behaviorName, $this->_deniedBehaviors[$state]) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if a behavior is allowed in a state
	 *
	 * @param string $behaviorName The name of the behavior to check
	 *
	 * @return bool True if the behavior is allowed, false otherwise
	 */
	private function behaviorIsAllowed( $behaviorName ) {
		foreach ( $this->_state as $state => $args ) {
			if ( (Val::is($this->_allowedBehaviors[$state]) && Arr::count($this->_allowedBehaviors[$state] > 0) ) &&
				!in_array($behaviorName, $this->_allowedBehaviors[$state]) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Determines if a behavior can be performed based on its allowed/denied state
	 *
	 * @param string $behaviorName The name of the behavior to check
	 *
	 * @return bool True if the behavior can be performed, false otherwise
	 */
	public function can( $behaviorName ) {
		if ( $this->behaviorIsAllowed( $behaviorName) && !$this->behaviorIsDenied($behaviorName) ) {
			return parent::can( $behaviorName );
		} else {
			return false;
		}
	}

	/**
	 * Adds denied behaviors for a behavior
	 *
	 * @param mixed $behavior The behavior to deny, can be a string or an instance of Behavior
	 * @param mixed $behavioral_implication The implication of the behavior, can be a string, an array of strings or an instance of Behavior
	 */
	public function denies( $behavior, $behavioral_implication ) {
		$behaviorName = ( $behavior instanceof Behavior ) ? $behavior->name() : $behavior;
		$behavioral_implication = Arr::toArray($behavioral_implication);
			
		if ( $this->can($behaviorName) ) {
			$behavior = ( $behavior instanceof Behavior) ? $behavior : new Behavior($behaviorName);
			$this->_deniedBehaviors[$behaviorName] = [];
			foreach( $behavioral_implication as $implication ) {
				$impliedBehaviorName = ( $implication instanceof Behavior ) ? $implication->name() : $implication;

				$this->_deniedBehaviors[$behaviorName][] = $impliedBehaviorName;
			}
		}
	}

	/**
	 * Adds allowed behaviors for a behavior
	 *
	 * @param mixed $behavior The behavior to allow, can be a string or an instance of Behavior
	 * @param mixed $behavioral_implication The implication of the behavior, can be a string, an array of strings or an instance of Behavior
	 */
	public function allows( $behavior, $behavioral_implication ) {
		$behaviorName = ( $behavior instanceof Behavior ) ? $behavior->name() : $behavior;
		$behavioral_implication = Arr::toArray($behavioral_implication);
			
		if ( $this->can($behaviorName) ) {
			$behavior = ( $behavior instanceof Behavior) ? $behavior : new Behavior($behaviorName);
			$this->_deniedBehaviors[$behaviorName] = [];
			foreach( $behavioral_implication as $implication ) {
				$impliedBehaviorName = ( $implication instanceof Behavior ) ? $implication->name() : $implication;

				$this->_allowedBehaviors[$behaviorName][] = $impliedBehaviorName;
			}
		}
	}

	/**
	 * Adds behavioral implications for a behavior
	 *
	 * @param mixed $behavior The behavior to imply, can be a string or an instance of Behavior
	 * @param mixed $behavioral_implication The implications of the behavior, can be a string, an array of strings or an instance of Behavior
	 */
	public function implies( $behavior, $behavioral_implication ) {
	$behaviorName = ( $behavior instanceof Behavior ) ? $behavior->name() : $behavior;
	$behavioral_implication = Arr::toArray($behavioral_implication);
		
	if ( $this->can($behaviorName) ) {
		$behavior = ( $behavior instanceof Behavior) ? $behavior : new Behavior($behaviorName);
		foreach( $behavioral_implication as $implication ) {
			try {
				$this->handler( new Handler( $behavior, function() use ($implication) {
						$this->perform($implication);
					}));
				} catch ( InvalidArgumentException $e ) {
					error_log( $e->getMessage() );
				}
			}
		}
	}

	/**
	 * Supresses the behavioral implications of a behavior.
	 *
	 * @param mixed $behavior The behavior to suppress implications for.
	 * @param mixed $behavioral_implication The behavioral implications to suppress.
	 *
	 * @return void
	 */
	public function supresses( $behavior, $behavioral_implication ) {
		// Get the behavior name string
		$behaviorName = ( $behavior instanceof Behavior ) ? $behavior->name() : $behavior;
		$behavioral_implication = Arr::toArray($behavioral_implication);
			
		if ( $this->can($behaviorName) ) {
			$behavior = ( $behavior instanceof Behavior) ? $behavior : new Behavior($behaviorName);
			foreach( $behavioral_implication as $implication ) {
				try {
					$this->handler( new Handler( $behavior, function() use ($implication) {
						$this->halt($implication);
					}));
				} catch ( InvalidArgumentException $e ) {
					error_log( $e->getMessage() );
				}
			}
		}
	}
}