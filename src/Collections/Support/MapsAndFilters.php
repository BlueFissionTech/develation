<?php

namespace BlueFission\Collections\Support;

/**
 * Shared traversal helpers for array-like value objects.
 */
trait MapsAndFilters
{
    /**
     * Map values while preserving their original keys.
     *
     * @param array $values
     * @param callable $callback Receives value, and key when accepted.
     * @return array
     */
    protected function mapArrayValues(array $values, callable $callback): array
    {
        $mapped = [];
        $acceptsKey = $this->callbackAcceptsKey($callback);

        foreach ($values as $key => $value) {
            $mapped[$key] = $acceptsKey ? $callback($value, $key) : $callback($value);
        }

        return $mapped;
    }

    /**
     * Filter values while preserving retained keys.
     *
     * @param array $values
     * @param callable $callback Receives value, and key when accepted.
     * @return array
     */
    protected function filterArrayValues(array $values, callable $callback): array
    {
        $filtered = [];
        $acceptsKey = $this->callbackAcceptsKey($callback);

        foreach ($values as $key => $value) {
            $keep = $acceptsKey ? $callback($value, $key) : $callback($value);

            if ($keep) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Determine whether a callback can receive a key as the second argument.
     *
     * @param callable $callback
     * @return bool
     */
    private function callbackAcceptsKey(callable $callback): bool
    {
        $reflection = new \ReflectionFunction(\Closure::fromCallable($callback));

        return $reflection->isVariadic() || $reflection->getNumberOfParameters() > 1;
    }
}
