<?php

namespace BlueFission\Prototypes\Contracts;

/**
 * CollectiveAware
 *
 * Marker interface for prototype carriers that can join or leave shared-fate
 * groupings such as flocks, fleets, mobs, or clouds.
 */
interface CollectiveAware
{
    /**
     * Join a collective membership record.
     *
     * @param mixed $collective
     * @param array<string, mixed> $meta
     * @return static
     */
    public function joinCollective(mixed $collective, array $meta = []): static;

    /**
     * Remove a collective membership record.
     *
     * @param mixed $collective
     * @return static
     */
    public function leaveCollective(mixed $collective): static;

    /**
     * Return all collective memberships.
     *
     * @return array<int, array<string, mixed>>
     */
    public function collectives(): array;

    /**
     * Determine whether the carrier belongs to a named collective.
     *
     * @param string $name
     * @return bool
     */
    public function inCollective(string $name): bool;
}
