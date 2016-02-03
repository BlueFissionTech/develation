<?php
namespace BlueFission\Data\Datasource;

use BlueFission\DevNumber;
use BlueFission\Data\Data;
use BlueFission\Data\IData;

class Datasource extends Data implements IData {
	$_index;
	$_collection;

	public function __construct( $config = null ) {
		parent::__construct( $config = null );
		$_index = -1;
	}

	public function read() {
		$this->assign( $this->_collection[ $this->_index ] );
	}
	public function write() {
		$this->_collection[ $this->_index ] = $this->_data;
	}
	public function delete() {
		unset ( $this->_collection[ $this->_index ] );
	}
	public function contents() {
		return serialize( $this->_data );
	}

	public function index( $index = 0 ) {
		if ( $index && $this->inbounds( $index ) ) {
			$this->_index = $index;
		}
		return $this->_index;
	}

	private function inbounds( $index = null ) {
		$index = DevNumber::isValid($index) ? $index : $this->_index;
		return ( $index <= count( $this->_collection ) && $index >= 0 );
	}

	public function next() {
		if ( $this->inbounds() )
			$this->_index++;
	}

	public function previous() {
		if ( $this->inbounds() )
			$this->_index--;
	}
}