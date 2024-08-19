<?php
// DEPRECATED IN FAVOR OF "HIERARCHICAL"
namespace BlueFission\Collections;

class Container extends Hierarchical implements ICollection
{
	public function __construct( )
	{
		parent::__construct();
		// $this->parent = null;
		// $this->value = new Collection();
	} 
	// public function get( $label )
	// {
	// 	$this->value->get( $label );
	// }
	// public function has( $label )
	// {
	// 	$this->value->has( $label );
	// }
	// public function add( $object, $label = null )
	// {
	// 	$object->parent($this);
	// 	$key = $object->label($label);

	// 	$this->value->add( $object, $label );
	// }
	public function contents()
	{
		return $this->value->contents();
	}
	// public function remove( $label )
	// {
	// 	$this->value->remove( $label );
	// }
}