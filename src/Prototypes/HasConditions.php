<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\DevElation as Dev;
use BlueFission\Prototypes\Contracts\Conditional;

/**
 * HasConditions
 *
 * Provides normalized condition registration and lightweight evaluation
 * against runtime context, state, and object properties. This is intentionally
 * generic so downstream systems can layer richer reasoning on top.
 */
trait HasConditions
{
    /**
     * Add one condition record, shorthand string, or resolver callback.
     *
     * @param mixed $condition
     * @param array<string, mixed> $meta
     * @return static
     */
    public function addCondition(mixed $condition, array $meta = []): static
    {
        $conditions = Arr::toArray($this->prototypeGet('conditions', []));
        $conditions[] = $this->normalizeConditionRecord($condition, $meta);

        return $this->prototypeSet('conditions', $conditions, 'prototypes.conditions.added');
    }

    /**
     * Return all normalized condition records.
     *
     * @return array<int, array<string, mixed>>
     */
    public function conditions(): array
    {
        return Arr::toArray($this->prototypeGet('conditions', []));
    }

    /**
     * Determine whether a named condition is registered.
     *
     * @param string $name
     * @return bool
     */
    public function hasCondition(string $name): bool
    {
        $name = Str::trim($name);
        foreach ($this->conditions() as $condition) {
            if (($condition['name'] ?? '') === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether every registered condition is satisfied.
     *
     * @param array<string, mixed> $context
     * @return bool
     */
    public function conditionsMet(array $context = []): bool
    {
        foreach ($this->conditions() as $condition) {
            if (!$this->prototypeEvaluateConditionRecord($condition, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return only the conditions that are not satisfied by the current context.
     *
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public function unmetConditions(array $context = []): array
    {
        $unmet = [];

        foreach ($this->conditions() as $condition) {
            if (!$this->prototypeEvaluateConditionRecord($condition, $context)) {
                $unmet[] = $condition;
            }
        }

        return $unmet;
    }

    /**
     * Normalize shorthand conditions into a common record structure.
     *
     * @param mixed $condition
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    protected function normalizeConditionRecord(mixed $condition, array $meta = []): array
    {
        if (is_callable($condition)) {
            return $this->prototypeMergeArrays([
                'name' => 'condition_' . uniqid(),
                'resolver' => $condition,
                'operator' => 'resolver',
                'confidence' => 1.0,
            ], $meta);
        }

        if (is_array($condition)) {
            return $this->prototypeMergeArrays([
                'name' => (string) ($condition['name'] ?? 'condition_' . uniqid()),
                'path' => $condition['path'] ?? $condition['property'] ?? null,
                'expected' => $condition['expected'] ?? ($condition['value'] ?? true),
                'operator' => $condition['operator'] ?? 'requires',
                'confidence' => (float) ($condition['confidence'] ?? 1.0),
            ], $condition, $meta);
        }

        return $this->prototypeMergeArrays([
            'name' => (string) $condition,
            'path' => (string) $condition,
            'expected' => true,
            'operator' => 'requires',
            'confidence' => 1.0,
        ], $meta);
    }

    /**
     * Evaluate one normalized condition record against runtime context.
     *
     * @param array<string, mixed> $condition
     * @param array<string, mixed> $context
     * @return bool
     */
    protected function prototypeEvaluateConditionRecord(array $condition, array $context = []): bool
    {
        $condition = Dev::apply('prototypes.conditions.evaluate.in', $condition);

        if (isset($condition['resolver']) && is_callable($condition['resolver'])) {
            return (bool) call_user_func($condition['resolver'], $context, $this, $condition);
        }

        $path = $condition['path'] ?? null;
        $expected = $condition['expected'] ?? true;
        $operator = (string) ($condition['operator'] ?? 'requires');

        if ($path && is_string($path)) {
            $actual = $this->prototypeContextValue($context, $path, null);

            if ($actual === null && method_exists($this, 'property')) {
                $actual = $this->property($path);
            }

            if ($actual === null) {
                $state = $this->prototypeGet('state', []);
                if (is_array($state) && Arr::hasKey($state, $path)) {
                    $actual = $state[$path];
                }
            }

            return $this->prototypeCompare($actual, $expected, $operator);
        }

        return $this->prototypeCompare(true, $expected, $operator);
    }
}
