<?php
namespace BlueFission\Behavioral;

/**
 * Interface IBehavioral
 * 
 * This interface is for the classes that perform behaviors and manage states based on behaviors and events
 */
interface IBehavioral
{
	/**
     * Performs a behavior on the object.
     *
     * @param string|Behavior $behavior The behavior to perform.
     * @throws InvalidArgumentException If an invalid behavior type is passed.
     * @throws NotImplementedException If the behavior is not implemented.
     */
    public function perform( );

	/**
	 * Check if the behavior can be performed.
	 * 
	 * @param string $behaviorName The name of the behavior.
	 * 
	 * @return bool True if the behavior can be performed, false otherwise.
	 */
	public function can( $behaviorName );

	/**
	 * Check if the object has a specific behavior.
	 * 
	 * @param string $behaviorName The name of the behavior to check for.
	 * 
	 * @return mixed The last behavior if $behaviorName is null,
	 * 				true if the object has the behavior,
	 * 				false otherwise.
	 */
	public function is( $behaviorName = null );

	/**
	 * Halt the specified behavior.
	 * 
	 * @param string $behaviorName The name of the behavior to halt.
	 */
	public function halt( $behaviorName );

	/**
	 * Get or set the value of the specified field.
	 * 
	 * @param string $field The name of the field.
	 * @param mixed $value The value to set.
	 * 
	 * @return mixed The value of the field.
	 */
	public function field(string $field, $value = null): mixed;

	/**
	 * Clear the object.
	 */
	public function clear(): void;
}
