<?php
namespace BlueFission\Data

use BlueFission;
use BlueFission\Collections\Hierarchical;

class File extends Hierarchical
{
	private $_contents;
	
	const PATH_SEPARATOR = DIRECTORY_SEPARATOR;
	
	public function __construct() {
		parent::__construct();
	}
	
	public function contents($data = null) {
		if (DevValue::isNull($data)) return $this->_contents;
		
		$this->_contents = $data;
	}
	
	public function append($data) {
		$this->_contents .= $data;
	}
	
	public function write() {
		if ( method_exists($this->_root, 'write') ) // or is callable?
		{
			$storage = new ReflectionClass( get_class( $this->_root ) );
			
			$this->_root->config( $storage->getStaticPropertyValue('NAME_FIELD'), $this->_label );
			$this->_root->config( $storage->getStaticPropertyValue('PATH_FIELD'), implode( $storage->getStaticPropertyValue('PATH_SEPARATOR'), $this->path() ) );
			$this->_root->contents( $this->contents() );
			$this->_root->write();
		}
	}
}