<?php
namespace BlueFission;

interface IDevObject
{
    /**
     * This method sets or gets the value of a field
     * 
     * @param string $var the field name
     * @param mixed $value the value of the field. If null, the method returns the value of the field
     * @return mixed
     */
	public function field( $var, $value = null );
    
    /**
     * This method should clear all fields
     * 
     * @return void
     */
	public function clear();
}
