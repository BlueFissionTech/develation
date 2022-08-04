<?php
namespace BlueFission\Data\Storage\Structure;

use BlueFission\Data\Storage\Mysql;

class MysqlScaffold implements IScaffold {
	public function create( $entity, callable $processor ) {
		$structure = new Structure($entity);
		call_user_func_array($processor, [$structure]);
		$query = $structure->build();

		$this->_mysql->run($query);
	}

	public function alter( $entity, callable $processor ) {

	}

	public function delete( $entity ) {

	}	
}