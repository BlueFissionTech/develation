<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\Prototypes\Contracts\CollectiveAware;

trait HasCollectives
{
    public function joinCollective(mixed $collective, array $meta = []): static
    {
        $memberships = Arr::toArray($this->prototypeGet('collective_memberships', []));
        $normalized = $this->prototypeMergeArrays([
            'collective' => $this->prototypeSnapshotValue($collective),
        ], $meta);
        $memberships[] = $normalized;

        return $this->prototypeSet('collective_memberships', $memberships, 'prototypes.collectives.joined');
    }

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

    public function collectives(): array
    {
        return Arr::toArray($this->prototypeGet('collective_memberships', []));
    }

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
