<?php

namespace BlueFission\Prototypes\Contracts;

/**
 * Causal
 *
 * Marker interface for prototype carriers that store candidate causes and
 * effects and can surface filtered inference results.
 */
interface Causal
{
    /**
     * Add a causal candidate record or resolver.
     *
     * @param mixed $cause
     * @param array<string, mixed> $meta
     * @return static
     */
    public function addCause(mixed $cause, array $meta = []): static;

    /**
     * Add an effect record or resolver.
     *
     * @param mixed $effect
     * @param array<string, mixed> $meta
     * @return static
     */
    public function addEffect(mixed $effect, array $meta = []): static;

    /**
     * Return stored cause candidates.
     *
     * @return array<int, array<string, mixed>>
     */
    public function causes(): array;

    /**
     * Return stored effect candidates.
     *
     * @return array<int, array<string, mixed>>
     */
    public function effects(): array;

    /**
     * Filter and rank cause candidates against the provided context.
     *
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public function inferCauses(array $context = []): array;

    /**
     * Filter and rank effect candidates against the provided context.
     *
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public function inferEffects(array $context = []): array;
}
