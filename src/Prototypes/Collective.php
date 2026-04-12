<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\Collections\Group;
use BlueFission\Str;
use BlueFission\Val;

/**
 * Collective
 *
 * Domain-friendly grouping substrate for members that share rules, state,
 * position, or destiny. It captures semantic grouping without hardcoding a
 * specific simulation or graph engine.
 */
trait Collective
{
    /**
     * Runtime registry of member objects before they are snapshotted into data.
     *
     * @var Group|null
     */
    protected ?Group $_collectiveMembers = null;

    /**
     * Lazily build the internal member registry.
     *
     * @return Group
     */
    protected function collectiveRegistry(): Group
    {
        if (!$this->_collectiveMembers instanceof Group) {
            $this->_collectiveMembers = new Group();
        }

        return $this->_collectiveMembers;
    }

    /**
     * Get or assign the semantic kind of collective.
     *
     * @param string|null $kind
     * @return mixed
     */
    public function collectiveKind(?string $kind = null): mixed
    {
        $this->prototypeBoot('collective');
        $this->kind('collective');

        if (Val::isNull($kind)) {
            return (string) $this->prototypeGet('collective_kind', 'collective');
        }

        return $this->prototypeSet('collective_kind', Str::trim($kind), 'prototypes.collective.kind_set');
    }

    /**
     * Add one member to the collective registry and snapshot store.
     *
     * @param mixed $member
     * @param string|null $key
     * @return static
     */
    public function addMember(mixed $member, ?string $key = null): static
    {
        $key = $key ?? (is_object($member) && method_exists($member, 'protoId') ? $member->protoId() : uniqid('member_', true));
        $this->collectiveRegistry()->add($member, $key);

        return $this->prototypeSet(
            'collective_members',
            $this->collectiveRegistry()->map(fn ($item) => $this->prototypeSnapshotValue($item))->toArray(),
            'prototypes.collective.member_added'
        );
    }

    /**
     * Return the snapshotted collective members.
     *
     * @return array<int|string, mixed>
     */
    public function members(): array
    {
        return Arr::toArray($this->prototypeGet('collective_members', []));
    }

    /**
     * Get or assign one named collective rule.
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function collectiveRule(string $name, mixed $value = null): mixed
    {
        $rules = Arr::toArray($this->prototypeGet('collective_rules', []));

        if (func_num_args() === 1) {
            return $rules[$name] ?? null;
        }

        $rules[$name] = $this->prototypeSnapshotValue($value);

        return $this->prototypeSet('collective_rules', $rules, 'prototypes.collective.rule_changed');
    }

    /**
     * Return all collective rules.
     *
     * @return array<string, mixed>
     */
    public function collectiveRules(): array
    {
        return Arr::toArray($this->prototypeGet('collective_rules', []));
    }

    /**
     * Get the entire shared state bag, one state value, or assign one value.
     *
     * @param string|null $name
     * @param mixed $value
     * @return mixed
     */
    public function collectiveState(?string $name = null, mixed $value = null): mixed
    {
        $state = Arr::toArray($this->prototypeGet('collective_state', []));

        if (Val::isNull($name)) {
            return $state;
        }

        if (func_num_args() === 1) {
            return $state[$name] ?? null;
        }

        $state[$name] = $this->prototypeSnapshotValue($value);

        return $this->prototypeSet('collective_state', $state, 'prototypes.collective.state_changed');
    }

    /**
     * Get or assign the shared destiny/intent descriptor for the collective.
     *
     * @param mixed $destiny
     * @return mixed
     */
    public function sharedDestiny(mixed $destiny = null): mixed
    {
        if (Val::isNull($destiny)) {
            return $this->prototypeGet('collective_destiny');
        }

        return $this->prototypeSet('collective_destiny', $this->prototypeSnapshotValue($destiny), 'prototypes.collective.destiny_set');
    }
}
