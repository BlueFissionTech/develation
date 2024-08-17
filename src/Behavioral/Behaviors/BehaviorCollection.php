<?php
namespace BlueFission\Behavioral\Behaviors;

use BlueFission\Collections\ICollection;
use BlueFission\Collections\Collection;

/**
 * Class BehaviorCollection
 *
 * A collection class that holds multiple behaviors.
 */
class BehaviorCollection extends Collection {

    /**
     * Add a behavior to the collection.
     *
     * @param Behavior $_behavior The behavior to add.
     * @param string|null $_label An optional label for the behavior.
     */
    public function add( $_behavior, $_label = null ): ICollection
    {
        if (!$this->has($_behavior->name()))
            parent::add( $_behavior );

        return $this;
    }

    /**
     * Get a behavior from the collection by its name.
     *
     * @param string $_behaviorName The name of the behavior to retrieve.
     * @return Behavior|null The behavior with the given name, or null if it doesn't exist in the collection.
     */
    public function get( $_behaviorName ) {
        foreach ($this->_value as $_c) {
            if ($_c->name() == $_behaviorName)
                return $_c;
        }
    }

    /**
     * Check if a behavior with the given name exists in the collection.
     *
     * @param string $_behaviorName The name of the behavior to check for.
     * @return bool True if a behavior with the given name exists, false otherwise.
     */
    public function has( $_behaviorName ) {
        foreach ($this->_value as $_c) {
            if ($_c->name() == $_behaviorName)
                return true;
        }
    }
}