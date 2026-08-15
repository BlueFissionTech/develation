<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Preparers;
use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\DevElation as Dev;
use BlueFission\Arr;

class PreparerRegistry
{
    protected static array $preparers = [];
    protected static array $scopes = [];

    /**
     * Register a preparer, replacing an existing preparer when a key is supplied.
     *
     * Unkeyed registrations remain persistent until explicitly unregistered by
     * the returned numeric key. Scoped registrations can be released together.
     *
     * @return int|string The stable registry key.
     */
    public static function register(
        IElementPreparer $preparer,
        ?array $supports = null,
        int|string|null $key = null,
        ?string $scope = null
    ): int|string
    {
        $preparer = Dev::apply('_in', $preparer);
        if ($supports !== null) {
            $preparer->setsSupported($supports);
        }

        if ($key === null) {
            self::$preparers[] = $preparer;
            $key = array_key_last(self::$preparers);
        } else {
            self::$preparers[$key] = $preparer;
        }

        if ($scope === null) {
            unset(self::$scopes[$key]);
        } else {
            self::$scopes[$key] = $scope;
        }

        Dev::do('_after', [$preparer, $supports]);

        return $key;
    }

    public static function get(int|string $key): ?IElementPreparer
    {
        return Dev::apply('_out', self::$preparers[$key] ?? null);
    }

    /**
     * Remove one registration by its stable key.
     */
    public static function unregister(int|string $key): bool
    {
        if (!Arr::hasKey(self::$preparers, $key)) {
            return false;
        }

        unset(self::$preparers[$key], self::$scopes[$key]);

        return true;
    }

    /**
     * Remove every registration owned by a scope.
     *
     * Persistent registrations without a scope are not affected.
     */
    public static function release(string $scope): int
    {
        $released = 0;

        foreach (self::$scopes as $key => $registeredScope) {
            if ($registeredScope === $scope && self::unregister($key)) {
                $released++;
            }
        }

        return $released;
    }

    public static function all(): array
    {
        return Dev::apply('_out', Arr::values(self::$preparers));
    }

    public static function registerDefaults(): void
    {
        self::register(new Preparers\VariablePreparer(), key: 'default.variable');
        self::register(new Preparers\PathPreparer(), key: 'default.path');
        self::register(new Preparers\HierarchyPreparer(), key: 'default.hierarchy');
        self::register(new Preparers\EventBubblePreparer(), key: 'default.event_bubble');
    }
}
