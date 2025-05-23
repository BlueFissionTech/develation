<?php

/**
 * Interface for data manipulation.
 */

namespace BlueFission\Data;

use BlueFission\IObj;
use BlueFission\Behavioral\IConfigurable;

interface IData extends IObj, IConfigurable
{
    /**
     * Reads data from source.
     *
     * @return IObj
     */
    public function read(): IObj;

    /**
     * Writes data to source.
     *
     * @return IObj
     */
    public function write(): IObj;

    /**
     * Deletes data from source.
     *
     * @return IObj
     */
    public function delete(): IObj;

    /**
     * Returns data.
     *
     * @return mixed
     */
    public function data(): mixed;

    /**
     * Returns data contents.
     *
     * @return mixed
     */
    public function contents(): mixed;

    /**
     * Returns the status message of an operation.
     *
     * @param string $message
     * @return mixed
     */
    public function status($message = null): mixed;
}
