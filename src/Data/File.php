<?php

namespace BlueFission\Data;

use BlueFission;
use BlueFission\Behavioral\Dispatches;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Collections\ICollection;
use BlueFission\Collections\Hierarchical;
use BlueFission\Val;
use ReflectionClass;

/**
 * Class File
 *
 * Represents a virtual file that can read from or write to an underlying storage system.
 * Supports dispatch behavior and hierarchical structure.
 *
 * @package BlueFission\Data
 */
class File extends Hierarchical implements IDispatcher
{
    use Dispatches {
        Dispatches::__construct as private __dispatchesConstruct;
    }

    /**
     * The contents of the file.
     *
     * @var string
     */
    private $_contents;

    /**
     * The character used to separate path segments.
     */
    public const PATH_SEPARATOR = DIRECTORY_SEPARATOR;

    /**
     * File constructor.
     *
     * Initializes the hierarchy and dispatch system.
     */
    public function __construct()
    {
        parent::__construct(); // Set up hierarchy
        $this->__dispatchesConstruct(); // Initialize Dispatches trait
    }

    /**
     * Get or set the contents of the file.
     *
     * @param string|null $data If provided, sets the file contents. If null, returns current contents.
     * @return string|self
     */
    public function contents($data = null): mixed
    {
        if (Val::isNull($data)) {
            return $this->_contents;
        }

        $this->_contents = $data;
        return $this;
    }

    /**
     * Append content to the file.
     *
     * @param string $data Content to append.
     * @return ICollection Returns self for chaining.
     */
    public function append($data): ICollection
    {
        $this->_contents .= $data;
        return $this;
    }

    /**
     * Read content and metadata from the storage backend into this file.
     *
     * @return ICollection Returns self for chaining.
     */
    public function read(): ICollection
    {
        // Check if storage has a readable interface
        if (method_exists($this->_root, 'read')) {
            $storage = new ReflectionClass(get_class($this->_root));

            // Read metadata using static field names
            $this->_label = $this->_root->config($storage->getStaticPropertyValue('NAME_FIELD'));

            $path = $this->_root->config($storage->getStaticPropertyValue('PATH_FIELD'));
            $separator = $storage->getStaticPropertyValue('PATH_SEPARATOR');
            $this->path(explode($separator, $path));

            $this->contents($this->_root->contents());
        }

        return $this;
    }

    /**
     * Write the current file contents and metadata to the storage backend.
     *
     * @return ICollection Returns self for chaining.
     */
    public function write(): ICollection
    {
        // Check if storage has a write method
        if (method_exists($this->_root, 'write')) {
            $storage = new ReflectionClass(get_class($this->_root));

            // Write metadata
            $this->_root->config($storage->getStaticPropertyValue('NAME_FIELD'), $this->_label);
            $this->_root->config(
                $storage->getStaticPropertyValue('PATH_FIELD'),
                implode($storage->getStaticPropertyValue('PATH_SEPARATOR'), $this->path())
            );

            // Write contents
            $this->_root->contents($this->contents());
            $this->_root->write();
        }

        return $this;
    }
}
