<?php
namespace BlueFission\Data\Storage\Structure;

interface IScaffold {
	static function create( $entity, callable $processor );
	static function alter( $entity, callable $processor );
	static function delete( $entity );
}