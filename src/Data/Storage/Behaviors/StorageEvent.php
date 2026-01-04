<?php

namespace BlueFission\Data\Storage\Behaviors;

use BlueFission\Behavioral\Behaviors\Event;

/**
 * Class StorageEvent
 *
 * @package BlueFission\Data\Storage\Behaviors
 *
 * This class is a child class of Event and extends it to handle specific storage events.
 */
class StorageEvent extends Event
{
    /**
     * Const ACTIVATED
     *
     * @var string OnStorageActivated
     *
     * This constant holds the value for an event triggered when the storage is activated.
     */
    public const ACTIVATED = 'OnStorageActivated';
}
