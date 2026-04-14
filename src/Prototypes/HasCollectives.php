<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\Prototypes\Contracts\CollectiveAware;

/**
 * HasCollectives
 *
 * Tracks membership in one or more collectives while keeping membership data
 * snapshot-friendly for storage, tracing, and downstream world modeling.
 */
trait HasCollectives
{
    /**
     * Join a collective and optionally attach membership metadata.
     *
     * @param mixed $collective
     * @param array<string, mixed> $meta
     * @return static
     */
    public function joinCollective(mixed $collective, array $meta = []): static
    {
        $memberships = Arr::toArray($this->prototypeGet('collective_memberships', []));
        $normalized = $this->prototypeMergeArrays([
            'collective' => $this->prototypeSnapshotValue($collective),
        ], $meta);
        $memberships[] = $normalized;

        return $this->prototypeSet('collective_memberships', $memberships, 'prototypes.collectives.joined');
    }

    /**
     * Remove one collective membership by matching the collective snapshot.
     *
     * @param mixed $collective
     * @return static
     */
    public function leaveCollective(mixed $collective): static
    {
        $needle = $this->prototypeSnapshotValue($collective);
        $memberships = [];

        foreach (Arr::toArray($this->prototypeGet('collective_memberships', [])) as $membership) {
            if (($membership['collective'] ?? null) === $needle) {
                continue;
            }
            $memberships[] = $membership;
        }

        return $this->prototypeSet('collective_memberships', $memberships, 'prototypes.collectives.left');
    }

    /**
     * Return all collective membership records.
     *
     * @return array<int, array<string, mixed>>
     */
    public function collectives(): array
    {
        return Arr::toArray($this->prototypeGet('collective_memberships', []));
    }

    /**
     * Determine whether the carrier belongs to a named collective.
     *
     * @param string $name
     * @return bool
     */
    public function inCollective(string $name): bool
    {
        $name = Str::trim($name);

        foreach ($this->collectives() as $membership) {
            $collective = $membership['collective'] ?? null;

            if (is_array($collective)) {
                if (($collective['id'] ?? null) === $name || ($collective['name'] ?? null) === $name) {
                    return true;
                }
            } elseif ((string) $collective === $name) {
                return true;
            }
        }

        return false;
    }
}
