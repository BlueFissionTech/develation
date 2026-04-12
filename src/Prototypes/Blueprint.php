<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\Val;

/**
 * Blueprint
 *
 * Semantic template for artifacts, entities, or agents. A blueprint captures
 * defaults, constraints, capabilities, and components without implying that a
 * concrete instance already exists.
 */
trait Blueprint
{
    /**
     * Get or assign the archetypal name of the blueprint.
     *
     * @param string|null $archetype
     * @return mixed
     */
    public function archetype(?string $archetype = null): mixed
    {
        $this->prototypeBoot('blueprint');
        $this->kind('blueprint');

        if (Val::isNull($archetype)) {
            return (string) $this->prototypeGet('archetype', '');
        }

        return $this->prototypeSet('archetype', Str::trim($archetype), 'prototypes.blueprint.archetype_set');
    }

    /**
     * Get or replace the structural schema associated with the blueprint.
     *
     * @param array<string, mixed>|null $schema
     * @return mixed
     */
    public function schema(?array $schema = null): mixed
    {
        if (Val::isNull($schema)) {
            return Arr::toArray($this->prototypeGet('schema', []));
        }

        return $this->prototypeSet('schema', Arr::toArray($schema), 'prototypes.blueprint.schema_set');
    }

    /**
     * Get or replace default seed data used when instantiating this blueprint.
     *
     * @param array<string, mixed>|null $defaults
     * @return mixed
     */
    public function defaults(?array $defaults = null): mixed
    {
        if (Val::isNull($defaults)) {
            return Arr::toArray($this->prototypeGet('defaults', []));
        }

        return $this->prototypeSet('defaults', Arr::toArray($defaults), 'prototypes.blueprint.defaults_set');
    }

    /**
     * Add one named constraint to the blueprint definition.
     *
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function addConstraint(string $name, mixed $value): static
    {
        return $this->prototypeAssocSet('constraints', $name, $value, 'prototypes.blueprint.constraint_added');
    }

    /**
     * Return all named blueprint constraints.
     *
     * @return array<string, mixed>
     */
    public function constraints(): array
    {
        return Arr::toArray($this->prototypeGet('constraints', []));
    }

    /**
     * Register a named capability or capability level on the blueprint.
     *
     * @param string $name
     * @param mixed $level
     * @return static
     */
    public function capability(string $name, mixed $level = true): static
    {
        return $this->prototypeAssocSet('capabilities', $name, $level, 'prototypes.blueprint.capability_added');
    }

    /**
     * Return all named blueprint capabilities.
     *
     * @return array<string, mixed>
     */
    public function capabilities(): array
    {
        return Arr::toArray($this->prototypeGet('capabilities', []));
    }

    /**
     * Register a named component for the blueprint definition.
     *
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function component(string $name, mixed $value): static
    {
        return $this->prototypeAssocSet('components', $name, $value, 'prototypes.blueprint.component_added');
    }

    /**
     * Return all named blueprint components.
     *
     * @return array<string, mixed>
     */
    public function components(): array
    {
        return Arr::toArray($this->prototypeGet('components', []));
    }

    /**
     * Build an instantiated seed payload from defaults and overrides.
     *
     * @param array<string, mixed> $seed
     * @return array<string, mixed>
     */
    public function instantiate(array $seed = []): array
    {
        $instance = $this->prototypeMergeArrays($this->defaults(), $seed);
        $instance['blueprint'] = $this->protoId() ?: $this->name();
        $instance['kind'] = $instance['kind'] ?? 'artifact';

        $this->prototypeSignal('prototypes.blueprint.instantiated', ['seed' => $seed, 'instance' => $instance, 'object' => $this]);

        return $instance;
    }
}
