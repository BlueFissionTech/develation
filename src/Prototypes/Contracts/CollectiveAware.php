<?php

namespace BlueFission\Prototypes\Contracts;

interface CollectiveAware
{
    public function joinCollective(mixed $collective, array $meta = []): static;

    public function leaveCollective(mixed $collective): static;

    public function collectives(): array;

    public function inCollective(string $name): bool;
}
