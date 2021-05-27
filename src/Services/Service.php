<?php
namespace BlueFission\Services;

use ReflectionClass;
use BlueFission\DevValue;
use BlueFission\DevObject;
use BlueFission\DevArray;
use BlueFission\Behavioral\Dispatcher;
use BlueFission\Behavioral\Behaviors\Behavior;

class Service extends Dispatcher {

	protected $_registrations;
	protected $_routes;
	protected $_parent;

	const LOCAL_LEVEL = 1;
	const SCOPE_LEVEL = 2;
	
	protected $_data = array(
		'name'=>'',
		'arguments'=>'',
		'instance'=>'',
		'type'=>'',
		'scope'=>'',
	);

	public function __construct() 
	{
		parent::__construct();
		$this->_registrations = array();
		$this->scope = $this;
	}

	public function instance()
	{
		if ( isset( $this->instance ) && $this->instance instanceof $this->type ) {
			$service = $this->instance;
		} else {
			$reflection_class = new ReflectionClass($this->type);
			$args = DevArray::toArray( $this->arguments );
    		$this->instance = $reflection_class->getConstructor() ? $reflection_class->newInstanceArgs( $args ) : $reflection_class->newInstanceWithoutConstructor();

			foreach ($this->_registrations as $name=>$registrations) {
				usort($registrations, function ($a, $b) {
					if ($a['priority'] == $b['priority']) {
						return 0;
					}
					return ($a['priority'] < $b['priority']) ? -1 : 1;
				});
				foreach ($registrations as $registration) {
					$this->apply( $registration );
				}
			}
		}
		return $this->instance;
	}

	public function name() {
		return $this->name;
	}

	public function parent( $object = null ) {
		if ( DevValue::isNotNull($object) )
			$this->_parent = $object;

		return $this->_parent;
	}

	public function broadcast( $behavior )
	{
		// echo "broadcasting $behavior from ".$this->name();
		if ( $behavior instanceof Behavior ) {
			// $behavior->_target = $this->_parent;
			$behavior->_target = $this;
		}
		// $this->_parent->boost($behavior);
		$this->dispatch( $behavior );
	}

	public function message( $behavior, $args = null ) {
		$instance = $this->instance();
		if ( $instance instanceof Dispatcher && is_callable( array( $instance, 'behavior') ) ) {
			// var_dump($behavior);
			// $instance->dispatch( $behavior, $args );
			// echo "Getting on it with ".\BlueFission\DevString::truncate($args, 10)."\n";
			
			// $this->dispatch($behavior, $args);
			$instance->dispatch($behavior, $args);
		} else {
			$this->call( $behavior, $args );
		}
	}

	public function call( $call, $args )
	{
		if ( is_callable(array($this->instance(), $call) ) )
		{
			$return = call_user_func_array( array($this->instance, $call), $args );
			return $return;
		}
	}

	public function register( $name, $handler, $level = self::LOCAL_LEVEL, $priority = 0 )
	{
		$registration = array('handler'=>$handler, 'level'=>$level, 'priority'=>$priority);
		$this->_registrations[$name][] = $registration;
		
		if ( isset( $this->instance ) && $this->instance instanceof $this->type ) {
			$this->apply( $registration );
		}
	}

	private function prepareCallback( $callback ) {
		if ( \is_object($callback) ) {
			$callback = $callback->bindTo($this->scope, $this->instance);
		} elseif (\is_string($callback) && (\strpos($callback, '::') !== false)) {
			$function = \explode('::', $callback);
			$callback = array($callback[0], $function[1]);
		} elseif (\is_string($callback)) {
			$callback = array($this->instance, $callback);
		}

		if (\is_array($callback) && count( $callback ) == 2) {
			if ( $this->instance instanceof $callback[0] ) {
				$callback[0] = $this->instance;
			}
		}

		return $callback;
	}

	private function apply( $registration ) {
		$level = $registration['level'];
		$handler = $registration['handler'];
		$callback = $handler->callback();
		$this->scope = (\is_object($this->scope)) ? $this->scope : $this->instance;
		// $scope = (\is_object($this->scope)) ? $this->scope : $this;

		$callback = $this->prepareCallback($callback);

		if ( $level == self::SCOPE_LEVEL && $this->scope instanceof Dispatcher && is_callable( array( $this->scope, 'behavior')) )
		{
			// $this->scope->behavior($handler->name(), $this->broadcast);
			if ( $this->instance instanceof Dispatcher && is_callable( array( $this->instance, 'behavior')) ) {	
				$this->instance->behavior($handler->name(), $callback);
			} else {
				$this->scope->behavior($handler->name(), $callback);
			}
			$this->scope->behavior($handler->name(), $this->message);
			// die(var_dump($this->scope));
		}
		elseif ( $level == self::LOCAL_LEVEL && $this->instance instanceof Dispatcher && is_callable( array( $this->instance, 'behavior')) )
		{
			// $this->behavior($handler->name(), $callback);
			// $this->instance->behavior($handler->name(), $callback);
			// $this->instance->behavior($handler->name(), $this->broadcast);

			$this->instance->behavior($handler->name(), $callback);
			$this->instance->behavior($handler->name(), $this->message);
			$this->instance->behavior($handler->name(), $this->broadcast);

			// if ( $this->scope == $this ) {
			// 	$this->instance->behavior($handler->name(), array($scope, 'broadcast'));
			// }

			// $this->scope->behavior($handler->name(), $this->broadcast);
			// $this->behavior($handler->name(), array($scope->_parent, 'broadcast'));
		} else {
			$this->behavior($handler->name(), $callback);
			// $this->behavior($handler->name(), $this->broadcast);
		}		
	}

	// public function dispatch( $behavior, $args = null ) {
	// 	// echo "{$behavior}\n";
	// 	if ( $behavior instanceof Behavior && $behavior->_target == $this->instance ) {
	// 		$behavior->_target = $this;
	// 	}
	// 	parent::dispatch($behavior, $args);
	// }
}