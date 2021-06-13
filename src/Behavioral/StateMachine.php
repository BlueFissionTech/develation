<?php
namespace BlueFission\Behavioral;

use BlueFission\DevArray;
use BlueFission\Behavioral\Programmable;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Handler;

class StateMachine extends Programmable {

	protected $_denied_behaviors = [];
	protected $_allowed_behaviors = [];

	private function behaviorIsDenied($behaviorName)
	{
		foreach ( $this->_state as $state => $args ) {
			if ( ( isset($this->_denied_behaviors[$state]) && count($this->_denied_behaviors[$state] > 0) ) &&
				in_array($behaviorName, $this->_denied_behaviors[$state]) ) {
				return true;
			}
		}
		return false;
	}

	private function behaviorIsAllowed($behaviorName)
	{
		foreach ( $this->_state as $state => $args ) {
			if ( (isset($this->_allowed_behaviors[$state]) && count($this->_allowed_behaviors[$state] > 0) ) &&
				!in_array($behaviorName, $this->_allowed_behaviors[$state]) ) {
				return false;
			}
		}
		return true;
	}

	public function can( $behaviorName )
	{

		if ( $this->behaviorIsAllowed( $behaviorName) && !$this->behaviorIsDenied($behaviorName) )
			return parent::can( $behaviorName );
		else
			return false;
	}

	public function denies( $behavior, $behavioral_implication ) {
		$behaviorName = ( $behavior instanceof Behavior ) ? $behavior->name() : $behavior;
		$behavioral_implication = DevArray::toArray($behavioral_implication);
			
		if ( $this->can($behaviorName) ) {
			$behavior = ( $behavior instanceof Behavior) ? $behavior : new Behavior($behaviorName);
			$this->_denied_behaviors[$behaviorName] = [];
			foreach( $behavioral_implication as $implication ) {
				$impliedBehaviorName = ( $implication instanceof Behavior ) ? $implication->name() : $implication;

				$this->_denied_behaviors[$behaviorName][] = $impliedBehaviorName;
			}
		}
	}

	public function allows( $behavior, $behavioral_implication ) {
		$behaviorName = ( $behavior instanceof Behavior ) ? $behavior->name() : $behavior;
		$behavioral_implication = DevArray::toArray($behavioral_implication);
			
		if ( $this->can($behaviorName) ) {
			$behavior = ( $behavior instanceof Behavior) ? $behavior : new Behavior($behaviorName);
			$this->_denied_behaviors[$behaviorName] = [];
			foreach( $behavioral_implication as $implication ) {
				$impliedBehaviorName = ( $implication instanceof Behavior ) ? $implication->name() : $implication;

				$this->_allowed_behaviors[$behaviorName][] = $impliedBehaviorName;
			}
		}
	}

	public function implies( $behavior, $behavioral_implication ) {
		// Get the behavior name string
		$behaviorName = ( $behavior instanceof Behavior ) ? $behavior->name() : $behavior;
		$behavioral_implication = DevArray::toArray($behavioral_implication);
			
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

	public function supresses( $behavior, $behavioral_implication ) {
		// Get the behavior name string
		$behaviorName = ( $behavior instanceof Behavior ) ? $behavior->name() : $behavior;
		$behavioral_implication = DevArray::toArray($behavioral_implication);
			
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