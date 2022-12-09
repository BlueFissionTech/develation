<?php
namespace BlueFission\Data\Storage\Structure;

use BlueFission\Data\Storage\Mysql;

class MysqlScaffold implements IScaffold {
	static function create( $entity, callable $processor ) {

		$refFunction = new \ReflectionFunction($processor);
		$parameters = $refFunction->getParameters();
		$type = $parameters[0]->getType()->getName() ?? Structure::class;

		$structure = new $type($entity);
		call_user_func_array($processor, [$structure]);
		$query = $structure->build();

		$mysql = new Mysql();
		$mysql->activate();
		$mysql->run($query);
		print( "Creating {$entity}. " . $mysql->status(). "\n");
	}

	static function alter( $entity, callable $processor ) {

	}

	static function delete( $entity ) {
		$query = "DROP TABLE IF EXISTS `{$entity}`";
		$mysql = new Mysql();
		$mysql->activate();
		$mysql->run($query);
		print( "Dropping {$entity}. " . $mysql->status(). "\n");
	}	
}