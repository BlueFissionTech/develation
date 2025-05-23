<?php

namespace BlueFission\Behavioral\Behaviors;

use BlueFission\Collections\ICollection;
use BlueFission\Collections\Collection;

/**
 * Class BehaviorCollection
 *
 * A collection class for managing a group of behaviors.
 */
class BehaviorCollection extends Collection
{
    /**
     * Add a behavior to the collection.
     *
     * @param Behavior $behavior The behavior to add.
     * @param string|null $label Optional label (currently unused).
     * @return ICollection
     */
    public function add($behavior, $label = null): ICollection
    {
        if (!$this->has($behavior->name())) {
            parent::add($behavior);
        }

        return $this;
    }

    /**
     * Retrieve a behavior from the collection by name.
     *
     * @param string $behaviorName The name of the behavior to retrieve.
     * @return Behavior|null
     */
    public function get(string $behaviorName): ?Behavior
    {
        foreach ($this->_value as $c) {
            if ($c->name() === $behaviorName) {
                return $c;
            }
        }

        return null;
    }

    /**
     * Check if a behavior with the given name exists in the collection.
     *
     * @param string $behaviorName
     * @return bool
     */
    public function has(string $behaviorName): bool
    {
        foreach ($this->_value as $c) {
            if ($c->name() === $behaviorName) {
                return true;
            }
        }

        return false;
    }
}

/**
 * ✅ Improvement Summary:
 * - Added return types (`?Behavior`, `bool`, `ICollection`) for stricter type safety
 * - Added full PHPDoc for all methods and the class itself
 * - Used strict equality (`===`) for more reliable behavior name matching
 * - Ensured fallback return `null` for `get()` when no match is found
 * - Cleaned up formatting and spacing for consistency
 * - Removed unused `$label` logic for now but left parameter for future expansion
 */
