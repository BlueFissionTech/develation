<?php
namespace BlueFission\Collections;

class Hierarchical extends Collection implements ICollection
{
	protected $_root;
	protected $_parent;
	protected $_label;
	
	const PATH_SEPARATOR = '.';

	public function __construct( ) {
		parent::__construct();
		$this->_root = null;
	}

	public function label( $label = null ) {
		if ( is_scalar($label) ) $this->_label = $label;
		return $this->_label;
	}

	public function parent( $parent = null )
	{
		if ( $parent instanceof Hierarchical ) {
			$this->_parent = $parent;
		}
		return $this->_parent;
	}

	public function add( $object, $label = null)
	{
		$object->parent($this);
		$label = $object->label($label);
		parent::add($object, $label);
	}

	public function path() {
		$path = $this->_parent ? $this->_parent->path() : array();

		$path[] = $this->label(); 
		
		return $path;
	}

	public function contents()
	{
		return $this->_value->contents();
	}

}