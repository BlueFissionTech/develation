<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\Collections\Group;
use BlueFission\Val;

trait Domain
{
    protected ?Group $_domainMembers = null;
    protected ?Group $_domainSubdomains = null;
    protected ?Group $_domainCollectives = null;

    protected function domainMembersRegistry(): Group
    {
        if (!$this->_domainMembers instanceof Group) {
            $this->_domainMembers = new Group();
        }

        return $this->_domainMembers;
    }

    protected function domainSubdomainsRegistry(): Group
    {
        if (!$this->_domainSubdomains instanceof Group) {
            $this->_domainSubdomains = new Group();
        }

        return $this->_domainSubdomains;
    }

    protected function domainCollectivesRegistry(): Group
    {
        if (!$this->_domainCollectives instanceof Group) {
            $this->_domainCollectives = new Group();
        }

        return $this->_domainCollectives;
    }

    public function domainName(?string $name = null): mixed
    {
        $this->prototypeBoot('domain');
        $this->kind('domain');

        if (Val::isNull($name)) {
            return (string) $this->prototypeGet('name', '');
        }

        return $this->name($name);
    }

    public function addMember(mixed $member, ?string $key = null): static
    {
        $key = $key ?? (is_object($member) && method_exists($member, 'protoId') ? $member->protoId() : uniqid('member_', true));
        $this->domainMembersRegistry()->add($member, $key);

        return $this->prototypeSet(
            'domain_members',
            $this->domainMembersRegistry()->map(fn ($item) => $this->prototypeSnapshotValue($item))->toArray(),
            'prototypes.domain.member_added'
        );
    }

    public function members(): array
    {
        return Arr::toArray($this->prototypeGet('domain_members', []));
    }

    public function addSubdomain(mixed $domain, ?string $key = null): static
    {
        $key = $key ?? (is_object($domain) && method_exists($domain, 'protoId') ? $domain->protoId() : uniqid('subdomain_', true));
        $this->domainSubdomainsRegistry()->add($domain, $key);

        return $this->prototypeSet(
            'domain_subdomains',
            $this->domainSubdomainsRegistry()->map(fn ($item) => $this->prototypeSnapshotValue($item))->toArray(),
            'prototypes.domain.subdomain_added'
        );
    }

    public function subdomains(): array
    {
        return Arr::toArray($this->prototypeGet('domain_subdomains', []));
    }

    public function addCollective(mixed $collective, ?string $key = null): static
    {
        $key = $key ?? (is_object($collective) && method_exists($collective, 'protoId') ? $collective->protoId() : uniqid('collective_', true));
        $this->domainCollectivesRegistry()->add($collective, $key);

        return $this->prototypeSet(
            'domain_collectives',
            $this->domainCollectivesRegistry()->map(fn ($item) => $this->prototypeSnapshotValue($item))->toArray(),
            'prototypes.domain.collective_added'
        );
    }

    public function collectives(): array
    {
        return Arr::toArray($this->prototypeGet('domain_collectives', []));
    }

    public function rule(string $name, mixed $value = null): mixed
    {
        $rules = Arr::toArray($this->prototypeGet('domain_rules', []));

        if (func_num_args() === 1) {
            return $rules[$name] ?? null;
        }

        $rules[$name] = $this->prototypeSnapshotValue($value);

        return $this->prototypeSet('domain_rules', $rules, 'prototypes.domain.rule_changed');
    }

    public function rules(): array
    {
        return Arr::toArray($this->prototypeGet('domain_rules', []));
    }

    public function defaultValue(string $name, mixed $value = null): mixed
    {
        $defaults = Arr::toArray($this->prototypeGet('domain_defaults', []));

        if (func_num_args() === 1) {
            return $defaults[$name] ?? null;
        }

        $defaults[$name] = $this->prototypeSnapshotValue($value);

        return $this->prototypeSet('domain_defaults', $defaults, 'prototypes.domain.default_changed');
    }

    public function defaults(): array
    {
        return Arr::toArray($this->prototypeGet('domain_defaults', []));
    }

    public function domainState(?string $name = null, mixed $value = null): mixed
    {
        $state = Arr::toArray($this->prototypeGet('domain_state', []));

        if (Val::isNull($name)) {
            return $state;
        }

        if (func_num_args() === 1) {
            return $state[$name] ?? null;
        }

        $state[$name] = $this->prototypeSnapshotValue($value);

        return $this->prototypeSet('domain_state', $state, 'prototypes.domain.state_changed');
    }

    public function context(): array
    {
        return [
            'rules' => $this->rules(),
            'defaults' => $this->defaults(),
            'state' => $this->domainState(),
            'members' => $this->members(),
            'subdomains' => $this->subdomains(),
            'collectives' => $this->collectives(),
        ];
    }
}
