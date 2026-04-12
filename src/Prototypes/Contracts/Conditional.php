<?php

namespace BlueFission\Prototypes\Contracts;

/**
 * Conditional
 *
 * Marker interface for prototype carriers that expose normalized condition
 * records and can evaluate them against runtime context.
 */
interface Conditional
{
    /**
     * Add a normalized condition record or resolver.
     *
     * @param mixed $condition
     * @param array<string, mixed> $meta
     * @return static
     */
    public function addCondition(mixed $condition, array $meta = []): static;

    /**
     * Return all normalized condition records.
     *
     * @return array<int, array<string, mixed>>
     */
    public function conditions(): array;

    /**
     * Determine whether a named condition is registered.
     *
     * @param string $name
     * @return bool
     */
    public function hasCondition(string $name): bool;

    /**
     * Evaluate all registered conditions against the provided context.
     *
     * @param array<string, mixed> $context
     * @return bool
     */
    public function conditionsMet(array $context = []): bool;

    /**
     * Return any condition records that are not currently satisfied.
     *
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public function unmetConditions(array $context = []): array;
}
