<?php
namespace BlueFission\Behavioral;

/**
 * Interface IConfigurable
 * 
 * This interface is for the classes that require configurable options.
 */
interface IConfigurable
{
	/**
	 * config method
	 * 
	 * This method sets or retrieves a configurable option.
	 * 
	 * @param mixed $config The name of the configuration option.
	 * @param mixed $value The value to set for the configuration option.
	 * @return mixed The value of the configuration option.
	 */
	public function config( $config = null, $value = null );
}
