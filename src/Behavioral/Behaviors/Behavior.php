<?php

namespace BlueFission\Behavioral\Behaviors;

/**
 * Class Behavior
 *
 * Defines a behavior with metadata such as name, persistence, passivity, priority, and contextual target.
 */
class Behavior
{
    /**
     * @var string The name of the behavior.
     */
    private string $_name;

    /**
     * @var bool Whether the behavior is persistent.
     */
    protected bool $_persistent;

    /**
     * @var bool Whether the behavior is passive.
     */
    protected bool $_passive;

    /**
     * @var int The priority level of the behavior.
     */
    protected int $_priority;

    /**
     * @var object|null The object on which the behavior operates.
     */
    public mixed $target;

    /**
     * @var mixed The contextual data for the behavior.
     */
    public mixed $context;

    /**
     * Constructor to define a behavior's core properties.
     *
     * @param string $name Name of the behavior.
     * @param int $priority Optional priority (higher runs first).
     * @param bool $passive Whether the behavior is passive (non-triggering).
     * @param bool $persistent Whether the behavior should persist in the object state.
     */
    public function __construct(string $name, int $priority = 0, bool $passive = true, bool $persistent = true)
    {
        $this->_name = $name;
        $this->_persistent = $persistent;
        $this->_passive = $passive;
        $this->_priority = $priority;
        $this->target = null;
        $this->context = null;
    }

    /**
     * Get the name of the behavior.
     */
    public function name(): string
    {
        return $this->_name;
    }

    /**
     * Determine if the behavior is persistent.
     */
    public function is_persistent(): bool
    {
        return $this->_persistent;
    }

    /**
     * Determine if the behavior is passive.
     */
    public function is_passive(): bool
    {
        return $this->_passive;
    }

    /**
     * Get the priority value of the behavior.
     */
    public function priority(): int
    {
        return $this->_priority;
    }

    /**
     * Returns the behavior's name as a string.
     */
    public function __toString(): string
    {
        return $this->name();
    }
}
