<?php
namespace BlueFission\Data\Storage\Structure;

use BlueFission\Data\Storage\MySQL;

/**
 * Class MySQLScaffold
 *
 * @package BlueFission\Data\Storage\Structure
 */
class MySQLScaffold implements IScaffold {

	/**
	 * Creates a new MySQL table using the entity name and a processor to configure the structure.
	 *
	 * @param string $entity The name of the table to be created.
	 * @param callable $processor The function used to configure the structure of the table.
	 */
	static function create( $entity, callable $processor ) {

		$refFunction = new \ReflectionFunction($processor);
		$parameters = $refFunction->getParameters();
		$type = $parameters[0]->getType()->getName() ?? Structure::class;

		$structure = new $type($entity);
		call_user_func_array($processor, [$structure]);
		$query = $structure->build();

		$mysql = new MySQL(['location'=>null, 'name'=>$entity]);
		$mysql->activate();
		$mysql->run($query);
		$status = $mysql->status();

		if ($status != MySQL::STATUS_SUCCESS) {
			$status .= " " . $mysql->error();
		}
		print( "Creating {$entity}. " . $status . "\n");
	}

	/**
	 * Alters an existing MySQL table using the entity name and a processor to configure the structure.
	 *
	 * @param string $entity The name of the table to be altered.
	 * @param callable $processor The function used to configure the structure of the table.
	 */
	static function alter( $entity, callable $processor ) {

	}

	/**
	 * Deletes an existing MySQL table using the entity name.
	 *
	 * @param string $entity The name of the table to be deleted.
	 */
	static function delete( $entity ) {
		$query = "DROP TABLE IF EXISTS `{$entity}`";
		$mysql = new MySQL(['location'=>null, 'name'=>$entity]);
		$mysql->activate();
		$mysql->run($query);
		$status = $mysql->status();

		if ($status != MySQL::STATUS_SUCCESS) {
			$status .= " " . $mysql->error();
		}
		print( "Dropping {$entity}. " . $status . "\n");
	}	
}
