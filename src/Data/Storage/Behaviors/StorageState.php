<?php
/**
 * Class StorageState
 * 
 * @package BlueFission\Data\Storage\Behaviors
 *
 * Extends the Behavior's State class to define a set of constants 
 * that represent the state of a storage system.
 */
class StorageState extends State
{
    /**
     * Constant representing the busy state of the storage system.
     *
     * @var string
     */
    const BUSY = 'IsStorageBusy';
}
