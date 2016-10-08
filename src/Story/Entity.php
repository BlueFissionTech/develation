<?php

class Entity extends Programmable {

	// size, mass, temperature, charge, time?
	protected $_data = array(
		'substance'=>'',
		'cohesion'=>'',
		'nature'=>'',
		'movement'=>'',
		'intention'=>'',
	);

	protected $_contracts = array();

	protected function init() {
		parent::init();

		// Are you [arg]?
		// You are [arg].
		// What are you?

		// [message type] [priority] [subject] [modality] [behavior] [conditions] [object] [relationship] [indirect object] [position]
		// [statement|command|query] [priority] [subject property|entity|class] [modality] [behavior] [conditions] [object property|entity|class] [preposition] [indirect object] [active pos] [passive pos]

		// Are you ready

		// Query 100 Delta toBe {spectacles, testicles, wallet, watch} here now

		// Statement 0 Milkshakes toWill {conditions...} Lambda

		// Statement 10 Lamdba state toBe {child:mathys} here now

		// modality: state, potential, willed, ought, obligatory, imperative, inevitable

		$this->learn('like', function( $property = null ) {
			$likeness = new DevArray();
			
			foreach ( $object as $a=>$b ) {
				if ( is_string($a) && $subject->get($a) == $b ) {
					$likeness[$a] = $b;
				} else {
					if ( in_array($b, $subject) ) {
						$likeness[] = $b;
					}
				}
			}

			return $likeness;
		}, 'Is');

		$this->learn('does', function( $behavior = null) {
			$changes = new DevArray();
		
			foreach ( $object as $a=>$b ) {
				if ( is_string($a) && $subject->get($a) != $b ) {
					$changes[$a] = $b;
					$object[$a] = $b;
				} else {
					if ( !in_array($b, $subject) ) {
						$changes[] = $b;
						$object[] = $b;
					}
				}
			}

			return $changes;
		}, 'Do');

		$this->learn('will', function( $behavior = null) {
			// if isn't or can't do, support conditions to conduct
			while ( $desire ) {
			$desire = $this->_behaviors->has($behavior);
			return $desire;
		}, 'Will');

		$this->learn('handle', function( $behavior ) {
			$concern = $this->_handlers->has($behavior);
			return $concern;
		}, 'Concern');

		$this->learn('commit', function( $data ) {
			$committed = $this->_contracts->has($data);
			return $committed;
		}, 'Commit');

		$this->learn('query', function( $data) {
			// Ask somebody
		}, 'Query');

		$this->learn('intend', function ( $data ) {
			// What even?
		}, 'Understand');
	}
}