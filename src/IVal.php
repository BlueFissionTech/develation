<?php

namespace BlueFission;

use BlueFission\Collections\Collection;

interface IVal
{
    /**
     * Cast the internal value as the object representative datatype
     * @return IVal
     */
    public function cast(): IVal;

    /**
     * Set or return the value of the var
     *
     * @param mixed $value
     *
     * @return mixed The value of the data member `_data`
     */
    public function val($value = null): mixed;

    /**
     * Sets the var to null
     * @return IVal
     */
    public function clear(): IVal;

    /**
     * pass the value as a reference bound to $_data
     *
     * @param mixed $value
     * @return void
     */
    public function ref(&$value): IVal;

    /**
     * Snapshot the value of the var
     *
     * @return IVal
     */
    public function snapshot(): IVal;

    /**
     * Clear the value of the snapshot
     *
     * @return IVal
     */
    public function clearSnapshot(): IVal;

    /**
     * Reset the value of the var to the snapshot
     *
     * @return IVal
     */
    public function reset(): IVal;

    /**
     * Get the change between the current value and the snapshot
     *
     * @return mixed
     */
    public function delta();

    /**
     * Get the datatype name of the object
     * @return string
     */
    public function getType(): string;

    /**
     * Tag the object with a group to be tracked by the object class
     * @param string $group The group to tag the object with
     * @return IVal
     */
    public function tag($group = null): IVal;

    /**
     * Untag the object from the group
     * @param string $group The group to untag the object from
     * @return IVal
     */
    public function untag($group = null): IVal;

    /**
     * Does the internal value qualify as the datatype represented by the object
     *
     * @return boolean
     */
    public function _is(): bool;

    /**
     * Check if var is a valid instance of $_type
     *
     * @return bool
     */
    public function _isValid($value = null): bool;

    /**
     * Add a constraint to the objects internal value
     * @return IVal
     */
    public function _constraint($callable, $priority = 10): IVal;

    /**
     * Create a new object of this type
     *
     * @param  mixed $value The variable to build the object with
     * @return IVal        A new object of this type
     */
    public static function make($value = null): IVal;

    /**
     * Create a new instance of the class
     * @param  mixed $value The value to set as the data member
     * @return IVal        a new instance of the class
     */
    public static function grab(): mixed;

    /**
     * Use the last instance of the class
     * @return IVal
     */
    public static function use(): IVal;

    /**
     * Slot a function and bind it into the object
     * @param string $name The name of the slot
     * @param callable $callable The function to be slotted
     * @return IVal returns the object
     */
    public static function slot(string $name, callable $callable): IVal;

    /**
     * Get the group of objects tagged with the specified group
     * @param string $group The group to get the objects from=
     * @return Collection
     */
    public static function grp($group = null): Collection;
}
