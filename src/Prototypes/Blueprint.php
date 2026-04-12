<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\Val;

trait Blueprint
{
    public function archetype(?string $archetype = null): mixed
    {
        $this->prototypeBoot('blueprint');
        $this->kind('blueprint');

        if (Val::isNull($archetype)) {
            return (string) $this->prototypeGet('archetype', '');
        }

        return $this->prototypeSet('archetype', Str::trim($archetype), 'prototypes.blueprint.archetype_set');
    }

    public function schema(?array $schema = null): mixed
    {
        if (Val::isNull($schema)) {
            return Arr::toArray($this->prototypeGet('schema', []));
        }

        return $this->prototypeSet('schema', Arr::toArray($schema), 'prototypes.blueprint.schema_set');
    }

    public function defaults(?array $defaults = null): mixed
    {
        if (Val::isNull($defaults)) {
            return Arr::toArray($this->prototypeGet('defaults', []));
        }

        return $this->prototypeSet('defaults', Arr::toArray($defaults), 'prototypes.blueprint.defaults_set');
    }

    public function addConstraint(string $name, mixed $value): static
    {
        return $this->prototypeAssocSet('constraints', $name, $value, 'prototypes.blueprint.constraint_added');
    }

    public function constraints(): array
    {
        return Arr::toArray($this->prototypeGet('constraints', []));
    }

    public function capability(string $name, mixed $level = true): static
    {
        return $this->prototypeAssocSet('capabilities', $name, $level, 'prototypes.blueprint.capability_added');
    }

    public function capabilities(): array
    {
        return Arr::toArray($this->prototypeGet('capabilities', []));
    }

    public function component(string $name, mixed $value): static
    {
        return $this->prototypeAssocSet('components', $name, $value, 'prototypes.blueprint.component_added');
    }

    public function components(): array
    {
        return Arr::toArray($this->prototypeGet('components', []));
    }

    public function instantiate(array $seed = []): array
    {
        $instance = $this->prototypeMergeArrays($this->defaults(), $seed);
        $instance['blueprint'] = $this->protoId() ?: $this->name();
        $instance['kind'] = $instance['kind'] ?? 'artifact';

        $this->prototypeSignal('prototypes.blueprint.instantiated', ['seed' => $seed, 'instance' => $instance, 'object' => $this]);

        return $instance;
    }
}
