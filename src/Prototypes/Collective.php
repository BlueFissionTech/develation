<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\Collections\Group;
use BlueFission\Str;
use BlueFission\Val;

trait Collective
{
    protected ?Group $_collectiveMembers = null;

    protected function collectiveRegistry(): Group
    {
        if (!$this->_collectiveMembers instanceof Group) {
            $this->_collectiveMembers = new Group();
        }

        return $this->_collectiveMembers;
    }

    public function collectiveKind(?string $kind = null): mixed
    {
        $this->prototypeBoot('collective');
        $this->kind('collective');

        if (Val::isNull($kind)) {
            return (string) $this->prototypeGet('collective_kind', 'collective');
        }

        return $this->prototypeSet('collective_kind', Str::trim($kind), 'prototypes.collective.kind_set');
    }

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

    public function members(): array
    {
        return Arr::toArray($this->prototypeGet('collective_members', []));
    }

    public function collectiveRule(string $name, mixed $value = null): mixed
    {
        $rules = Arr::toArray($this->prototypeGet('collective_rules', []));

        if (func_num_args() === 1) {
            return $rules[$name] ?? null;
        }

        $rules[$name] = $this->prototypeSnapshotValue($value);

        return $this->prototypeSet('collective_rules', $rules, 'prototypes.collective.rule_changed');
    }

    public function collectiveRules(): array
    {
        return Arr::toArray($this->prototypeGet('collective_rules', []));
    }

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

    public function sharedDestiny(mixed $destiny = null): mixed
    {
        if (Val::isNull($destiny)) {
            return $this->prototypeGet('collective_destiny');
        }

        return $this->prototypeSet('collective_destiny', $this->prototypeSnapshotValue($destiny), 'prototypes.collective.destiny_set');
    }
}
