<?php

namespace BlueFission\Prototypes\Contracts;

interface Conditional
{
    public function addCondition(mixed $condition, array $meta = []): static;

    public function conditions(): array;

    public function hasCondition(string $name): bool;

    public function conditionsMet(array $context = []): bool;

    public function unmetConditions(array $context = []): array;
}
