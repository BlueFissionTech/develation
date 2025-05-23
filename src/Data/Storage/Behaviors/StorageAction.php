<?php

namespace BlueFission\Data\Storage\Behaviors;

use BlueFission\Behavioral\Behaviors\Action;

/**
 * Class StorageAction
 *
 * @package BlueFission\Data\Storage\Behaviors
 *
 * This class extends the Action class and defines constants for different storage actions.
 *
 */
class StorageAction extends Action
{
    /**
     * Constant to represent read action
     *
     * @var string
     */
    public const READ = 'DoStorageRead';

    /**
     * Constant to represent write action
     *
     * @var string
     */
    public const WRITE = 'DoStorageWrite';

    /**
     * Constant to represent delete action
     *
     * @var string
     */
    public const DELETE = 'DoStorageDelete';
}
