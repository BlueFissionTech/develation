<?php
namespace BlueFission\Data\Storage\Structure;

interface IScaffold {
	public function create( $entity, callable $processor );
	public function alter( $entity, callable $processor );
	public function delete( $entity );
}