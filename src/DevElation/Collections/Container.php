<?php
namespace BlueFission\Collections;

class Container extends Hierarchical implements ICollection
{
	protected $_children;
	
	public function __construct( )
	{
		parent::__construct();
		$this->_root = null;
		$this->_parent = new ICollection();
		$this->_children = &$this->_value = new Collection();
	} 
	public function get( $label )
	{
		$this->_children->get( $label );
	}
	public function has( $label )
	{
		$this->_children->has( $label );
	}
	public function add( &$object, $label = null )
	{
		$this->_children->add( $label );
	}	
	public function contents()
	{
		return $this->_children->contents();
	}
	public function remove( $label )
	{
		$this->_children->remove( $label );
	}
}