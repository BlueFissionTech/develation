<?php

namespace BlueFission\Prototypes\Support;

use BlueFission\Arr;
use BlueFission\IVal;
use BlueFission\Obj;
use BlueFission\Str;
use BlueFission\Val;
use BlueFission\DevElation as Dev;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use LogicException;

/**
 * PrototypeTools
 *
 * Internal helper trait shared by the public prototype traits. It provides
 * `Obj`-backed storage helpers, normalization, hook emission, and optional
 * behavioral dispatch without introducing constructor coupling.
 */
trait PrototypeTools
{
    /**
     * Ensure prototype traits are only used on `Obj` carriers.
     *
     * @return void
     */
    protected function prototypeAssertCarrier(): void
    {
        if (!$this instanceof Obj) {
            throw new LogicException(
                sprintf('%s must extend %s to use prototype traits.', get_class($this), Obj::class)
            );
        }
    }

    /**
     * Seed the carrier with default prototype fields and optional kind.
     *
     * @param string|null $kind
     * @return void
     */
    protected function prototypeBoot(?string $kind = null): void
    {
        $this->prototypeAssertCarrier();

        $defaults = [
            'id' => '',
            'kind' => $kind ?? 'proto',
            'labels' => [],
            'traits' => [],
            'state' => [],
            'properties' => [],
            'measures' => [],
            'relations' => [],
            'conditions' => [],
            'causes' => [],
            'effects' => [],
            'confidence' => 0.0,
            'history' => [],
            'summary' => '',
            'domain' => null,
            'position' => [],
            'collective_memberships' => [],
        ];

        foreach ($defaults as $key => $value) {
            if (!Arr::hasKey($this->_data, $key) || Val::isNull($this->_data[$key])) {
                $this->_data[$key] = $value;
            }
        }

        if ($kind !== null && Str::isEmpty((string) ($this->_data['kind'] ?? ''))) {
            $this->_data['kind'] = $kind;
        }
    }

    /**
     * Read one prototype field from the underlying `Obj` data payload.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function prototypeGet(string $key, mixed $default = null): mixed
    {
        $this->prototypeBoot();

        if (!Arr::hasKey($this->_data, $key)) {
            return $default;
        }

        $value = $this->_data[$key];

        if ($value instanceof IVal) {
            return $value->val();
        }

        return $value;
    }

    /**
     * Set one prototype field and emit the matching hooks/events.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $hook
     * @return static
     */
    protected function prototypeSet(string $key, mixed $value, ?string $hook = null): static
    {
        $this->prototypeBoot();
        $this->_data[$key] = $value;

        $payload = ['key' => $key, 'value' => $this->prototypeSnapshotValue($value), 'object' => $this];
        $this->prototypeSignal($hook ?? 'prototypes.changed', $payload);

        return $this;
    }

    /**
     * Append a value to a list-like prototype field.
     *
     * @param string $key
     * @param mixed $value
     * @param bool $distinct
     * @param string|null $hook
     * @return static
     */
    protected function prototypeAppend(string $key, mixed $value, bool $distinct = false, ?string $hook = null): static
    {
        $values = Arr::toArray($this->prototypeGet($key, []));
        $candidate = $this->prototypeSnapshotValue($value);

        if (!$distinct || !Arr::contains($values, $candidate, true)) {
            $values[] = $candidate;
        }

        return $this->prototypeSet($key, $values, $hook);
    }

    /**
     * Set one named entry on an associative prototype field.
     *
     * @param string $key
     * @param string $name
     * @param mixed $value
     * @param string|null $hook
     * @return static
     */
    protected function prototypeAssocSet(string $key, string $name, mixed $value, ?string $hook = null): static
    {
        $values = Arr::toArray($this->prototypeGet($key, []));
        $values[$name] = $this->prototypeSnapshotValue($value);

        return $this->prototypeSet($key, $values, $hook);
    }

    /**
     * Emit a DevElation hook and optional behavioral events for prototype changes.
     *
     * @param string $hook
     * @param array<string, mixed> $data
     * @return void
     */
    protected function prototypeSignal(string $hook, array $data = []): void
    {
        Dev::do($hook, $data);

        if (method_exists($this, 'dispatch')) {
            $this->dispatch($hook, new Meta(data: $data, src: $this));
        }

        if (method_exists($this, 'trigger')) {
            $this->trigger(Event::CHANGE, new Meta(data: $data, src: $this));
        }
    }

    /**
     * Normalize values into snapshot-safe scalars or arrays.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function prototypeSnapshotValue(mixed $value): mixed
    {
        if ($value instanceof IVal) {
            return $value->val();
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->prototypeSnapshotValue($item);
            }
            return $normalized;
        }

        if (is_object($value)) {
            if (method_exists($value, 'snapshot')) {
                return $value->snapshot();
            }

            if (method_exists($value, 'toArray')) {
                return $value->toArray();
            }

            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
        }

        return $value;
    }

    /**
     * Resolve a dotted path from a runtime context payload.
     *
     * @param array<string, mixed> $context
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    protected function prototypeContextValue(array $context, string $path, mixed $default = null): mixed
    {
        return Arr::getPath($context, $path, $default);
    }

    /**
     * Compare actual and expected values using a normalized operator string.
     *
     * @param mixed $actual
     * @param mixed $expected
     * @param string $operator
     * @return bool
     */
    protected function prototypeCompare(mixed $actual, mixed $expected, string $operator = 'requires'): bool
    {
        return match ($operator) {
            'eq', 'equals', 'is' => $actual == $expected,
            'strict', '===' => $actual === $expected,
            'neq', 'not_equals', 'is_not' => $actual != $expected,
            'gt', '>' => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
            'gte', '>=' => is_numeric($actual) && is_numeric($expected) && $actual >= $expected,
            'lt', '<' => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
            'lte', '<=' => is_numeric($actual) && is_numeric($expected) && $actual <= $expected,
            'in' => is_array($expected) && Arr::contains($expected, $actual, true),
            'not_in' => is_array($expected) && !Arr::contains($expected, $actual, true),
            'truthy' => (bool) $actual,
            'falsy' => !(bool) $actual,
            default => (bool) $actual,
        };
    }

    /**
     * Sort weighted records in descending weight/confidence order.
     *
     * @param array<int, array<string, mixed>> $records
     * @return array<int, array<string, mixed>>
     */
    protected function prototypeSortByWeight(array $records): array
    {
        usort($records, function (array $a, array $b): int {
            $left = (float) ($a['weight'] ?? $a['confidence'] ?? 0.0);
            $right = (float) ($b['weight'] ?? $b['confidence'] ?? 0.0);

            return $right <=> $left;
        });

        return $records;
    }

    /**
     * Merge array payloads recursively while preserving unique list members.
     *
     * @param array<string|int, mixed> ...$arrays
     * @return array<string|int, mixed>
     */
    protected function prototypeMergeArrays(array ...$arrays): array
    {
        $merged = [];

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (
                    is_array($value)
                    && Arr::hasKey($merged, $key)
                    && is_array($merged[$key])
                ) {
                    $merged[$key] = $this->prototypeMergeArrays($merged[$key], $value);
                    continue;
                }

                if (is_int($key)) {
                    if (!Arr::contains($merged, $value, true)) {
                        $merged[] = $value;
                    }
                    continue;
                }

                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
