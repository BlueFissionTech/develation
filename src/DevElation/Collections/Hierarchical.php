<?php
namespace BlueFission\Collections;

use BlueFission\DevArray;

class Hierarchical
{
	protected $_root;
	protected $_parent;
	protected $_label;
	
	const PATH_SEPARATOR = '.';

	public function __construct( ) {
		parent::__construct();
		$this->_root = new Object();
	}
	public function label() {
		return $this->_label;
	}
	public function path() {
		$path = DevArray::toArray( $_parent->path() );
		$path[] = $this->label(); 
		return $path;
	}
}