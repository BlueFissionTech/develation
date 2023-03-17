<?php
namespace BlueFission\Behavioral\Behaviors;

/**
 * Class Behavior
 *
 * This class provides an implementation for defining behavior and its properties.
 */
class Behavior
{
	/**
	 * @var string $_name The name of the behavior.
	 */
	private $_name;

	/**
	 * @var bool $_persistent A flag to indicate whether the behavior should persist or not.
	 */
	protected $_persistent;
	
	/**
	 * @var bool $_passive A flag to indicate whether the behavior should be passive or not.
	 */
	protected $_passive;
	
	/**
	 * @var int $_priority The priority of the behavior, used to determine the order in which behaviors are executed.
	 */
	protected $_priority;
	
	/**
	 * @var object $_target The object on which the behavior is being defined.
	 */
	public $_target;
	
	/**
	 * @var mixed $_context The context in which the behavior is being defined.
	 */
	public $_context;

	/**
	 * Constructor for the Behavior class.
	 *
	 * @param string $name The name of the behavior.
	 * @param int $priority The priority of the behavior.
	 * @param bool $passive A flag to indicate whether the behavior should be passive or not.
	 * @param bool $persistent A flag to indicate whether the behavior should persist or not.
	 */
	public function __construct($name, $priority = 0, $passive = true, $persistent = true)
	{
		$this->_name = $name;
		$this->_persistent = $persistent;
		$this->_passive = $passive;
		$this->_priority = $priority;
		$this->_target = null;
	}	
	
	/**
	 * Get the name of the behavior.
	 *
	 * @return string The name of the behavior.
	 */
	public function name()
	{
		return $this->_name;
	}

	/**
	 * Check whether the behavior is persistent.
	 *
	 * @return bool True if the behavior is persistent, false otherwise.
	 */
	public function is_persistent()
	{
		return $this->_persistent;
	}

	/**
	 * Check whether the behavior is passive.
	 *
	 * @return bool True if the behavior is passive, false otherwise.
	 */
	public function is_passive()
	{
		return $this->_passive;
	}

	/**
	 * Get the priority of the behavior.
	 *
	 * @return int The priority of the behavior.
	 */
	public function priority()
	{
		return $this->_priority;
	}

	/**
	 * Return the name of the behavior as a string.
	 *
	 * @return string The name of the behavior.
	 */
	public function __toString()
	{
		return $this->name();
	}
}