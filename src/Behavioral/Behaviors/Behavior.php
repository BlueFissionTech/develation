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
	 * @var string $name The name of the behavior.
	 */
	private $name;

	/**
	 * @var bool $persistent A flag to indicate whether the behavior should persist or not.
	 */
	protected $persistent;
	
	/**
	 * @var bool $passive A flag to indicate whether the behavior should be passive or not.
	 */
	protected $passive;
	
	/**
	 * @var int $_priority The priority of the behavior, used to determine the order in which behaviors are executed.
	 */
	protected $_priority;
	
	/**
	 * @var object $target The object on which the behavior is being defined.
	 */
	public $target;
	
	/**
	 * @var mixed $context The context in which the behavior is being defined.
	 */
	public $context;

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
		$this->name = $name;
		$this->persistent = $persistent;
		$this->passive = $passive;
		$this->priority = $priority;
		$this->target = null;
	}	
	
	/**
	 * Get the name of the behavior.
	 *
	 * @return string The name of the behavior.
	 */
	public function name(): string
	{
		return $this->name;
	}

	/**
	 * Check whether the behavior is persistent.
	 *
	 * @return bool True if the behavior is persistent, false otherwise.
	 */
	public function is_persistent(): bool
	{
		return $this->persistent;
	}

	/**
	 * Check whether the behavior is passive.
	 *
	 * @return bool True if the behavior is passive, false otherwise.
	 */
	public function is_passive(): bool
	{
		return $this->passive;
	}

	/**
	 * Get the priority of the behavior.
	 *
	 * @return int The priority of the behavior.
	 */
	public function priority(): int
	{
		return $this->priority;
	}

	/**
	 * Return the name of the behavior as a string.
	 *
	 * @return string The name of the behavior.
	 */
	public function __toString(): string
	{
		return $this->name();
	}
}