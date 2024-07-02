<?php
namespace BlueFission;

interface IObj
{
    /**
     * This method sets or gets the value of a field
     * 
     * @param string $var the field name
     * @param mixed $value the value of the field. If null, the method returns the value of the field
     * @return mixed
     */
	public function field( string $var, $value = null );
    
    /**
     * This method should clear all fields
     * 
     * @return IObj
     */
	public function clear(): IObj;

    /**
     * This method should assign data to the object
     * 
     * @param mixed $data
     * @return IObj
     */
    public function assign( mixed $data ): IObj;
}
