<?php

namespace BlueFission\Behavioral;

/**
 * Interface IDispatcher
 *
 * This interface is for the classes that execute and dispatch behaviors
 */
interface IDispatcher
{
    /**
     * Adds a behavior to the behavior collection and creates a callback to trigger the behavior if specified.
     *
     * @param Behavior|string $behavior The behavior to be added. Can be a string or an instance of Behavior.
     * @param callable|null $callback The callback to trigger the behavior if specified to add as a handler for the behavior.
     *
     * @throws InvalidArgumentException if the behavior is not a string or an instance of Behavior.
     */
    public function behavior($behavior, $callback = null): IDispatcher;

    /**
     * Triggers a behavior by calling the trigger method with the behavior and arguments specified.
     *
     * @param mixed $behavior The behavior to be triggered. Can be a string or an instance of Behavior.
     * @param mixed|null $args The arguments to pass to the trigger method.
     */
    public function dispatch($behavior, $args = null): IDispatcher;

    /**
     * When, an alias for behavior, adds a behavior to the behavior collection and creates a callback to trigger the behavior if specified.
     *
     * @param Behavior|string $behavior The behavior to be added. Can be a string or an instance of Behavior.
     * @param callable|null $callback The callback to trigger the behavior if specified to add as a handler for the behavior.
     *
     * @throws InvalidArgumentException if the behavior is not a string or an instance of Behavior.
     */
    public function when($behavior, callable $callback): IDispatcher;

    /**
     * Repeats another Dispatcher's selected behaviors
     *
     * @param IDispatcher $otherObject The other object to repeat the behaviors from
     * @param mixed $behaviors The behaviors to repeat
     */
    public function echo(IDispatcher $otherObject, $behaviors): IDispatcher;
}
