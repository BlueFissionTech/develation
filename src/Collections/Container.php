<?php

// DEPRECATED IN FAVOR OF "HIERARCHICAL"

namespace BlueFission\Collections;

class Container extends Hierarchical implements ICollection
{
    public function __construct()
    {
        parent::__construct();
        // $this->_parent = null;
        // $this->_value = new Collection();
    }
    // public function get( $label )
    // {
    // 	$this->_value->get( $label );
    // }
    // public function has( $label )
    // {
    // 	$this->_value->has( $label );
    // }
    // public function add( $object, $label = null )
    // {
    // 	$object->parent($this);
    // 	$key = $object->label($label);

    // 	$this->_value->add( $object, $label );
    // }
    public function contents()
    {
        return $this->_value->contents();
    }
    // public function remove( $label )
    // {
    // 	$this->_value->remove( $label );
    // }
}
