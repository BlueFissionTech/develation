<?php
namespace BlueFission\Collections;

/**
 * Interface ICollection
 * 
 * An interface for defining basic collection functionality.
 */
interface ICollection {

	/**
	 * Returns an array of all objects in the collection
	 * 
	 * @return array
	 */
	public function contents();

	/**
	 * Adds an object to the collection
	 * 
	 * @param mixed $object
	 * @param mixed $label
	 * 
	 * @return void
	 */
	public function add( $object, $label = null );

	/**
	 * Checks if an object with the given label exists in the collection
	 * 
	 * @param mixed $label
	 * 
	 * @return bool
	 */
	public function has( $label );

	/**
	 * Returns the object with the given label from the collection
	 * 
	 * @param mixed $label
	 * 
	 * @return mixed
	 */
	public function get( $label );

	/**
	 * Removes the object with the given label from the collection
	 * 
	 * @param mixed $label
	 * 
	 * @return void
	 */
	public function remove( $label );
}
