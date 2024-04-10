<?php
namespace BlueFission;

interface IVal {
	/**
	 * Cast the internal value as the object representative datatype
	 * @return IVal
	 */
	public function cast(): IVal;

    /**
     * This method should return a value of some kind
     * 
     * @return mixed
     */
	public function val(): mixed;

	/**
	 * Sets the var to null
	 * @return IVal 
	 */
	public function clear(): IVal;

	/**
	 * pass the value as a reference bound to $_data
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function ref(&$value): IVal;

	/**
	 * Snapshot the value of the var
	 *
	 * @return IVal
	 */
	public function snapshot(): IVal;

	/**
	 * Clear the value of the snapshot
	 *
	 * @return IVal
	 */
	public function clearSnapshot(): IVal;

	/**
	 * Reset the value of the var to the snapshot
	 *
	 * @return IVal
	 */
	public function reset(): IVal;

	/**
	 * Get the change between the current value and the snapshot
	 *
	 * @return mixed
	 */
	public function delta();

	/**
	 * Does the internal value qualify as the datatype represented by the object
	 * 
	 * @return boolean 
	 */
	public function _is(): bool;

	/**
	 * Add a constraint to the objects internal value
	 * @return IVal
	 */
	public function _constraint( $callable, $priority = 10 ): IVal;

	/**
	 * Create a new object of this type
	 * 
	 * @param  mixed $value The variable to build the object with
	 * @return IVal        A new object of this type
	 */
	public static function make($value = null): IVal;
}