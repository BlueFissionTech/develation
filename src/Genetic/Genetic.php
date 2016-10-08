<?php
namespace BlueFission\Genetic;

use ReflectionClass;
use BlueFission\DevArray;
use BlueFission\Behavioral\Programmable;
use BlueFission\Behavioral\Configurable;

trait Genetic
{
	public function alter() 
	{
		if ( $this instanceof \BlueFission\DevObject ) {
			foreach ($this->_data as &$field) {
				if ( is_numeric($field) ) $field++;
			}
		}
	}

	public function accept( Visitor $visitor ) {
		
	}

	public function clone()
	{
		if ( $this instanceof \BlueFission\Behavioral\Configurable ) {
			$reflection_class = new ReflectionClass(get_class($this));
			$args = DevArray::toArray( $this->config() );
			$instance = $reflection_class->getConstructor() ? $reflection_class->newInstanceArgs( $args ) : $reflection_class->newInstanceWithoutConstructor();
			$instance->assign($this->_data);
		} elseif ( $this instanceof \BlueFission\DevObject ) {
			$reflection_class = new ReflectionClass(get_class($this));
			$instance = $reflection_class->getConstructor() ? $reflection_class->newInstanceArgs( $args ) : $reflection_class->newInstanceWithoutConstructor();
			$instance->assign($this->_data);
		} else {
			$instance = clone $this;
		}

		return $instance;
	}

	public function generate( &$target = null ) 
	{

		if ( $this instanceof \BlueFission\Behavioral\Configurable && $target instanceof \BlueFission\Behavioral\Configurable ) {
			if ($target->config()) {
				$config = $this->_config;
				$target->config( $config );
			}
		} else {
			return $this->clone();
		}
	}
}