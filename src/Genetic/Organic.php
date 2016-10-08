<?php
namespace BlueFission\Genetic;

use BlueFission\Behavioral\Scheme;

trait Organic {
	
	protected $_compartment;


	private function consume( &object ) {
		try {
			if ( is_object($object) ) {
				$internal_object = clone $object;
				unset($object);
			}
		} catch( Exception $e ) { 
		
		} finally {
			if ( $this instanceof Scheme ) {
				$this->perform('OnConsume');
			}
		}

	}

	private function digest() {

	}

	protected function init() {
		if ( $this instanceof Dispatcher ) {
			parent::init();
			$this->behavior( 'OnConsume' );
			$this->behavior( 'DoConsume' );
		}
	}
}