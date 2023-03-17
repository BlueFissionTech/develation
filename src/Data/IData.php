<?php
/**
 * Interface for data manipulation.
 */
namespace BlueFission\Data;

use BlueFission\IDevObject;
use BlueFission\Behavioral\IConfigurable;

interface IData extends IDevObject, IConfigurable
{
    /**
     * Reads data from source.
     *
     * @return mixed
     */
	public function read();

    /**
     * Writes data to source.
     *
     * @return mixed
     */
	public function write();

    /**
     * Deletes data from source.
     *
     * @return mixed
     */
	public function delete();

    /**
     * Returns data.
     *
     * @return mixed
     */
	public function data();

    /**
     * Returns data contents.
     *
     * @return mixed
     */
	public function contents();

    /**
     * Returns the status message of an operation.
     *
     * @param string $message
     * @return mixed
     */
	public function status( $message = null );
}
