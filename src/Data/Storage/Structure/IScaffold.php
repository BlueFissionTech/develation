<?php
namespace BlueFission\Data\Storage\Structure;

/**
 * Interface IScaffold
 * Defines methods to create, alter, and delete entities within a database structure.
 */
interface IScaffold {
	/**
	 * Creates a new database entity
	 *
	 * @param string $entity The name of the entity to create.
	 * @param callable $processor A callback function that processes the entity creation.
	 * @return void
	 */
	static function create( $entity, callable $processor );

	/**
	 * Alters an existing database entity
	 *
	 * @param string $entity The name of the entity to alter.
	 * @param callable $processor A callback function that processes the entity alteration.
	 * @return void
	 */
	static function alter( $entity, callable $processor );

	/**
	 * Deletes an existing database entity
	 *
	 * @param string $entity The name of the entity to delete.
	 * @return void
	 */
	static function delete( $entity );
}
