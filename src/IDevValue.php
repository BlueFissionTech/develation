<?php
namespace BlueFission;

interface IDevValue {
    /**
     * This method should return a value of some kind
     * 
     * @return mixed
     */
	public function value(): mixed;

	/**
	 * Sets the var to null
	 * @return void 
	 */
	public function clear(): void;
}