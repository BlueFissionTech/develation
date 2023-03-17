<?php
namespace BlueFission\Behavioral;

use \RuntimeException;
use BlueFission\DevValue;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\State;

/**
 * Class Programmable
 * 
 * Extends the Configurable class and provides additional methods for handling programmatic behaviors.
 */
class Programmable extends Configurable
{
	/**
	 * An array of tasks that can be performed by the object.
	 * 
	 * @var array $_tasks
	 */
	protected $_tasks;

	/**
	 * Programmable constructor.
	 * 
	 * Calls the parent constructor and initializes the `$_tasks` array.
	 */
	public function __construct( )
	{
		parent::__construct();
		$this->_tasks = array();
	}

	/**
	 * Overrides the default behavior of calling a method on an object.
	 * 
	 * If the called method exists within the object, it will be executed. If the method does not exist, but it is defined in the `$_tasks` array, it will be executed. Otherwise, a `RuntimeException` is thrown.
	 * 
	 * @param string $name The name of the method being called.
	 * @param array $args An array of arguments to pass to the method.
	 * 
	 * @throws \RuntimeException if the method does not exist in the object or in the `$_tasks` array.
	 * 
	 * @return mixed The result of the method call.
	 */
	public function __call($name, $args) 
	{
		if (method_exists ( $this , $name ))
		{
			return call_user_func_array(array($this, $name), $args);
		}
		if (isset($this->_tasks[$name]) && is_callable($this->_tasks[$name]))
		{
			$result = call_user_func_array($this->_tasks[$name], $args);
			$this->perform('On'.$name);
			return $result;
		}
		else 
		{
			throw new RuntimeException("Method {$name} does not exist");
		}
	}

	/**
	 * Adds a behavior to the object.
	 * 
	 * If the passed `$behavior` is a string, it will be converted to a `Behavior` object. The behavior is then added to the parent class.
	 * 
	 * @param mixed $behavior The behavior to be added. Can be either a string or a `Behavior` object.
	 * @param callable $callback A function to be executed when the behavior is triggered.
	 */
	public function behavior( $behavior, $callback = null ) {
		if ( is_string($behavior) && DevValue::isNotEmpty($behavior) ) {
			if ( strpos ( $behavior, 'Do') === 0 ) {
				$behavior = new Action($behavior);
			} elseif ( strpos ( $behavior, 'Is') === 0 ) {
				$behavior = new State($behavior);
			} elseif ( strpos ( $behavior, 'On') === 0 ) {
				$behavior = new Event($behavior);
			} else {
				$behavior = new Behavior($behavior);
			}
		}

		parent::behavior($behavior, $callback);
	}

	/**
	 * Learn a new task
	 * 
	 * @param string $task The name of the task to be learned
	 * @param callable $function The implementation of the task
	 * @param string $behavior The behavior to be applied on the task (optional)
	 * 
	 * @return bool True if the task was learned successfully, False otherwise
	 */
	public function learn($task, $function, $behavior = null )
	{
		if ( is_callable($function)
			&& (!array_key_exists($task, $this->_tasks) 
			&& $this->is( State::DRAFT )) )
		{
			$this->_tasks[$task] = $function->bindTo($this, $this);

			if ($behavior)
			{
				$this->behavior($behavior, $this->_tasks[$task]);
			}
			$this->behavior( 'On'.$task );
			$this->perform( Event::CHANGE );

			return true;
		}
		else
			return false;
	}

	/**
	 * Forget a learned task
	 * 
	 * @param string $task The name of the task to forget
	 */
	public function forget($task)
	{
		if ( $this->is( State::DRAFT ) && isset( $this->_tasks[$task] ) ) {
			unset( $this->_tasks[$task] );
			$this->perform( Event::CHANGE );
		}
	}

	/**
	 * Set a new field and learn a task with the same name if the value is callable
	 * 
	 * @param string $field The name of the field to set
	 * @param mixed $value The value of the field, or a callable function if learning a task
	 */
	public function __set($field, $value)
	{
		if (is_callable($value))
			$this->learn($field, $value);
		else
			parent::__set($field, $value);
	}
}