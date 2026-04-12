<?php

namespace BlueFission\Prototypes\Contracts;

interface Causal
{
    public function addCause(mixed $cause, array $meta = []): static;

    public function addEffect(mixed $effect, array $meta = []): static;

    public function causes(): array;

    public function effects(): array;

    public function inferCauses(array $context = []): array;

    public function inferEffects(array $context = []): array;
}
