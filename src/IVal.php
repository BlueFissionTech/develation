<?php
namespace BlueFission;

interface IVal {
    /**
     * This method should return a value of some kind
     * 
     * @return mixed
     */
	public function val(): mixed;

	/**
	 * Sets the var to null
	 * @return IVal 
	 */
	public function clear(): IVal;
}