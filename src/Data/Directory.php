<?php

namespace BlueFission\Data;

use BlueFission\Collections\Hierarchical;
use BlueFission\Collections\ICollection;
use BlueFission\Data\IData;

/**
 * Class Directory
 *
 * Represents a virtual or abstract directory structure that can store data.
 * Extends the Hierarchical class to allow parent-child relationships.
 * Implements ICollection for collection behavior.
 *
 * @package BlueFission\Data
 */
abstract class Directory extends Hierarchical implements ICollection
{
    /**
     * The root storage object backing the directory.
     *
     * @var IData
     */
    protected $_root;

    /**
     * Directory constructor.
     *
     * Initializes the directory with the provided IData storage backend.
     *
     * @param IData $storage The storage object that the directory will use.
     */
    public function __construct(IData $storage)
    {
        parent::__construct(); // Call the parent constructor to set up hierarchy
        $this->_root = $storage; // Store the provided data backend
    }
}
