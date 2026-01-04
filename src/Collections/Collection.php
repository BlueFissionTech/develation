<?php

namespace BlueFission\Collections;

use ArrayAccess;
use ArrayObject;
use ArrayIterator;
use IteratorAggregate;
use InvalidArgumentException;

/**
 * Class Collection
 *
 * An implementation of an array collection that implements ICollection, ArrayAccess, IteratorAggregate
 *
 * @package BlueFission\Collections
 * @author  Devon Scott <dscott@bluefission.com>
 * @link    https://bluefission.com/develation
 */
class Collection implements ICollection, ArrayAccess, IteratorAggregate
{
    /**
     * The actual value of the collection.
     *
     * @var ArrayObject
     */
    protected $_value;

    /**
     * The type of values in the collection.
     *
     * @var string
     */
    protected $_type = "";

    /**
     * Iterator for the collection.
     *
     * @var ArrayIterator
     */
    protected $_iterator;

    /**
     * Constructs a new Collection object.
     *
     * @param mixed $value Initial value for the collection.
     */
    public function __construct($value = null)
    {
        if (empty($value)) {
            $this->_value = new ArrayObject();
        } else {
            $this->_value = new ArrayObject((is_array($value)) ? $value : [$value]);
        }

        $this->_iterator = new ArrayIterator($this->_value);
    }

    /**
     * Gets the value stored at the given key in the collection.
     *
     * @param mixed $key Key to retrieve value from.
     *
     * @throws InvalidArgumentException If the key is not scalar or null.
     *
     * @return mixed Value stored at the given key.
     */
    public function get($key)
    {
        if (!is_scalar($key) && !is_null($key)) {
            throw new InvalidArgumentException('Label must be scalar');
        }
        if ($this->has($key)) {
            return $this->_value[$key];
        } else {
            return null;
        }
    }

    /**
     * Finds the index of the object in the collection.
     *
     * @param mixed $object The object to find.
     *
     * @return mixed The index of the object in the collection.
     */
    public function search($object): mixed
    {
        $found = array_search($object, $this->_value->getArrayCopy());
        return $found;
    }

    /**
     * Gets the collection as an array.
     *
     * @param bool $allow_empty Whether to allow empty values in the returned array.
     *
     * @return array Array representation of the collection.
     */
    public function toArray(bool $allow_empty = false)
    {
        $value = $this->_value->getArrayCopy();
        return $value;
    }

    /**
     * Check if the given key is present in the collection.
     *
     * @param mixed $key Key to check.
     *
     * @throws InvalidArgumentException If the key is not scalar or null.
     *
     * @return bool Whether the given key is present in the
     */
    public function has($key)
    {
        if (!is_scalar($key) && !is_null($key)) {
            throw new InvalidArgumentException('Label must be scalar');
        }
        // return is_object($this->_value) ? property_exists( $this->_value, $key ) : array_key_exists( $key, $this->_value );
        return $this->_value->offsetExists($key);
    }

    /**
     * Adds an object to the collection.
     *
     * @param mixed $object The object to add to the collection.
     * @param mixed|null $key The key to associate with the object.
     *
     * @throws InvalidArgumentException If the key is not scalar or null.
     */
    public function add($object, $key = null): ICollection
    {
        if (!is_scalar($key) && !is_null($key)) {
            throw new InvalidArgumentException('Label must be scalar');
        }
        $this->_value[$key] = $object;

        return $this;
    }

    /**
     * Adds an object to the collection if it's key is not already present.
     *
     * @param mixed $object The object to add to the collection.
     * @param mixed|null $key The key to associate with the object.
     *
     * @throws InvalidArgumentException If the key is not scalar or null.
     */

    public function addUnique($object, $key): ICollection
    {
        if (!is_scalar($key)) {
            throw new InvalidArgumentException('Label must be scalar');
        }
        if (!$this->has($key)) {
            $this->_value[$key] = $object;
        }

        return $this;
    }

    /**
     * Adds an object to the collection if it is not already present.
     *
     * @param mixed $object The object to add to the collection.
     * @param mixed|null $key The key to associate with the object.
     *
     * @throws InvalidArgumentException If the key is not scalar or null.
     */
    public function addDistinct($object, $key = null): ICollection
    {
        if (!is_scalar($key) && !is_null($key)) {
            throw new InvalidArgumentException('Label must be scalar');
        }
        if (!$this->contains($object)) {
            $this->_value[$key] = $object;
        }

        return $this;
    }

    /**
     * Gets the first object in the collection.
     *
     * @return mixed The first object in the collection.
     */
    public function first()
    {
        $array = $this->_value->getArrayCopy();
        $array = array_reverse($array);
        return end($array);
    }

    /**
     * Gets the last object in the collection.
     *
     * @return mixed The last object in the collection.
     */
    public function last()
    {
        $value = $this->_value->getArrayCopy();
        return end($value);
    }

    /**
     * Gets a copy of the objects in the collection.
     *
     * @return array An array of the objects in the collection.
     */
    public function contents()
    {
        return $this->_value->getArrayCopy();
    }

    /**
     * Removes an object from the collection.
     *
     * @param mixed $key The key of the object to remove.
     *
     * @throws InvalidArgumentException If the key is not scalar or null.
     */
    public function remove($key): ICollection
    {
        if (!is_scalar($key) && !is_null($key)) {
            throw new InvalidArgumentException('Label must be scalar');
        }
        if (isset($this->_value[$key])) {
            unset($this->_value[$key]);
        }

        return $this;
    }

    /**
     * Clears the collection of all objects.
     */
    public function clear(): ICollection
    {
        unset($this->_value);
        $this->_value = new ArrayObject();

        return $this;
    }

    /**
     * Gets the number of objects in the collection.
     *
     * @return int The number of objects in the collection.
     */
    public function count()
    {
        return $this->_value->count();
    }

    /**
     * Gets the current object in the collection and moves the pointer to the next one.
     *
     * @return mixed|false The current object in the collection, or false if there are no more objects.
     */
    public function next()
    {
        if ($this->valid()) {
            $row = $this->current();
            $this->tick();
            return $row;
        } else {
            $this->rewind();
            return false;
        }
    }

    /**
     * Yield the next item in the collection until complete
     */
    public function yield()
    {
        yield from $this->contents();
    }

    /**
     * Iterate a method across all items in the collection
     */
    public function each(callable $callback)
    {
        foreach ($this->contents() as $key => $value) {
            $callback($value, $key);
        }
    }

    /**
     * iterate a method across all items in the collection
     * @param  callable $callback The callback to apply to each item
     * @return bool
     */
    public function walk(callable $callback)
    {
        return array_walk($this->_data, $callback);
    }

    /**
     * Map, apply a function to each item in the collection
     * @param  callable $callback The callback to apply to each item
     * @return Collection
     */
    public function map(callable $callback)
    {
        $list = array_map($callback, $this->contents());
        return new Collection($list);
    }

    /**
     * Flatten, flatten the collection
     * @return Collection
     */
    public function flatten()
    {
        $list = $this->contents();
        $flat = [];
        foreach ($list as $item) {
            if (is_array($item)) {
                $flat = array_merge($flat, $item);
            } else {
                $flat[] = $item;
            }
        }
        return new Collection($flat);
    }

    public function flatMap(callable $callback)
    {
        return $this->map($callback)->flatten();
    }

    /**
     * Sort, apply a sorting fucntion to the collection
     * @param  callable $callback The callback to apply to each item
     * @return Collection
     */
    public function sort(callable $callback = null)
    {
        if (!$callback) {
            $callback = function ($a, $b) {
                return $a <=> $b;
            };
        }

        $list = $this->contents();
        usort($list, $callback);
        return new Collection($list);
    }

    /**
     * Filter, apply a filter to the collection
     * @param  callable $callback The callback to apply to each item
     * @return Collection
     */
    public function filter(callable $callback)
    {
        $list = array_filter($this->contents(), $callback);
        return new Collection($list);
    }

    /**
     * Serializes the collection.
     *
     * @return string A serialized string of the collection.
     */
    public function serialize()
    {
        return serialize($this->_value);
    }

    /**
     * Unserializes a string into a collection.
     *
     * @param string $data The serialized string.
     */
    public function unserialize($data)
    {
        $this->_value = unserialize($data);
    }

    /**
     * Array Access Methods
     *
     * Implementations of the ArrayAccess interface.
     *
     * @link https://www.php.net/manual/en/class.arrayaccess.php
     */

    /**
     * Check if the specified offset exists.
     *
     * @param int|string $offset The offset to check.
     * @return bool True if the offset exists, false otherwise.
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Get the value at the specified offset.
     *
     * @param int|string $offset The offset to retrieve.
     * @return mixed The value at the specified offset.
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Set the value at the specified offset.
     *
     * @param int|string $offset The offset to set.
     * @param mixed $value The value to set at the specified offset.
     */
    public function offsetSet($offset, $value): void
    {
        $this->add($value, $offset);
    }

    /**
     * Unset the value at the specified offset.
     *
     * @param int|string $offset The offset to unset.
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }

    /**
     * Iteration Methods
     *
     * Implementations of the Iterator interface.
     *
     * @link https://www.php.net/manual/en/class.iterator.php
     */

    /**
     * Rewind the iterator to the first element.
     */
    public function rewind()
    {
        $this->_iterator->rewind();
    }

    /**
     * Get the current element in the iterator.
     *
     * @return mixed The current element in the iterator.
     */
    public function current()
    {
        return $this->get($this->_iterator->key());
    }

    /**
     * Get the key of the current element in the iterator.
     *
     * @return int|string The key of the current element in the iterator.
     */
    public function key()
    {
        return $this->_iterator->key();
    }

    /**
     * Move the iterator to the next element.
     */
    public function tick()
    {
        return $this->_iterator->next();
    }

    /**
     * Check if the current iterator position is valid.
     *
     * @return bool True if the current iterator position is valid, false otherwise.
     */
    public function valid()
    {
        return $this->has($this->_iterator->key());
    }

    /**
     * Get the iterator for the collection.
     *
     * @return ArrayIterator The iterator for the collection.
     */
    public function getIterator(): ArrayIterator
    {
        $this->_iterator = $this->_iterator ?? new ArrayIterator($this->_value);
        return $this->_iterator;
    }

    /**
     * Check if the collection contains a specific value.
     *
     * @param mixed $value The value to check for.
     * @return bool True if the collection contains the specified value, false otherwise.
     */
    public function contains($value)
    {
        return in_array($value, $this->_value->getArrayCopy());
    }

    /**
     * Returns a random value from the current array.
     *
     * @return mixed The randomly selected value from the array.
     */
    public function rand()
    {
        // Doesn't work for associative key based arrays
        // $rand = 0;
        // if ( function_exists('mt_rand') ) {
        // 	$rand = mt_rand(0, $this->_value->count() - 1)];
        // } else {
        // 	$rand = array_rand( $this->_value->getArrayCopy() );
        // }
        if (count($this->_value) == 0) {
            return null;
        }
        $rand = array_rand($this->_value->getArrayCopy());

        return $this->_value[$rand];
    }
}
