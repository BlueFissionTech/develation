<?php

namespace BlueFission;

class Verb extends DevValue {
	protected $_name;

	public function process( $object )

	public function process( &$object, $args = null ) {
		$this->_value
	}
}

class Like extends Verb {
	public function process( &$object, $args = null ) {
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
	}	
}

class Does extends Verb {
	public function process( &$object, $args ) {
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
	}
}

class Will extends Verb {
	public function process( &$object, $args ) {
		$desires = new DevArray();
	}
}