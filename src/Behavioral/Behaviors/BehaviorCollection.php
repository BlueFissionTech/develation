<?php
namespace BlueFission\Behavioral\Behaviors;

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
     * @param Behavior $behavior The behavior to add.
     * @param string|null $label An optional label for the behavior.
     */
    public function add( $behavior, $label = null ) {
        if (!$this->has($behavior->name()))
            parent::add( $behavior );
    }

    /**
     * Get a behavior from the collection by its name.
     *
     * @param string $behaviorName The name of the behavior to retrieve.
     * @return Behavior|null The behavior with the given name, or null if it doesn't exist in the collection.
     */
    public function get( $behaviorName ) {
        foreach ($this->_value as $c) {
            if ($c->name() == $behaviorName)
                return $c;
        }
    }

    /**
     * Check if a behavior with the given name exists in the collection.
     *
     * @param string $behaviorName The name of the behavior to check for.
     * @return bool True if a behavior with the given name exists, false otherwise.
     */
    public function has( $behaviorName ) {
        foreach ($this->_value as $c) {
            if ($c->name() == $behaviorName)
                return true;
        }
    }
}