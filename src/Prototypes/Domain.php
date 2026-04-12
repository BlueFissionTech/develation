<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\Collections\Group;
use BlueFission\Val;

/**
 * Domain
 *
 * Shared world or context model for members, subdomains, collectives, rules,
 * defaults, and state. This is meant to be a universal substrate for app,
 * workflow, simulation, and world-model contexts.
 */
trait Domain
{
    /**
     * Runtime registry of direct domain members.
     *
     * @var Group|null
     */
    protected ?Group $_domainMembers = null;

    /**
     * Runtime registry of nested subdomains.
     *
     * @var Group|null
     */
    protected ?Group $_domainSubdomains = null;

    /**
     * Runtime registry of collectives associated with the domain.
     *
     * @var Group|null
     */
    protected ?Group $_domainCollectives = null;

    /**
     * Lazily build the member registry.
     *
     * @return Group
     */
    protected function domainMembersRegistry(): Group
    {
        if (!$this->_domainMembers instanceof Group) {
            $this->_domainMembers = new Group();
        }

        return $this->_domainMembers;
    }

    /**
     * Lazily build the subdomain registry.
     *
     * @return Group
     */
    protected function domainSubdomainsRegistry(): Group
    {
        if (!$this->_domainSubdomains instanceof Group) {
            $this->_domainSubdomains = new Group();
        }

        return $this->_domainSubdomains;
    }

    /**
     * Lazily build the collective registry.
     *
     * @return Group
     */
    protected function domainCollectivesRegistry(): Group
    {
        if (!$this->_domainCollectives instanceof Group) {
            $this->_domainCollectives = new Group();
        }

        return $this->_domainCollectives;
    }

    /**
     * Get or assign the human-readable domain name.
     *
     * @param string|null $name
     * @return mixed
     */
    public function domainName(?string $name = null): mixed
    {
        $this->prototypeBoot('domain');
        $this->kind('domain');

        if (Val::isNull($name)) {
            return (string) $this->prototypeGet('name', '');
        }

        return $this->name($name);
    }

    /**
     * Add one member to the domain registry and snapshot store.
     *
     * @param mixed $member
     * @param string|null $key
     * @return static
     */
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

    /**
     * Return all snapshotted domain members.
     *
     * @return array<int|string, mixed>
     */
    public function members(): array
    {
        return Arr::toArray($this->prototypeGet('domain_members', []));
    }

    /**
     * Add one nested subdomain to the registry and snapshot store.
     *
     * @param mixed $domain
     * @param string|null $key
     * @return static
     */
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

    /**
     * Return all snapshotted subdomains.
     *
     * @return array<int|string, mixed>
     */
    public function subdomains(): array
    {
        return Arr::toArray($this->prototypeGet('domain_subdomains', []));
    }

    /**
     * Add one collective to the domain registry and snapshot store.
     *
     * @param mixed $collective
     * @param string|null $key
     * @return static
     */
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

    /**
     * Return all snapshotted collectives in the domain.
     *
     * @return array<int|string, mixed>
     */
    public function collectives(): array
    {
        return Arr::toArray($this->prototypeGet('domain_collectives', []));
    }

    /**
     * Get or assign one named domain rule.
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function rule(string $name, mixed $value = null): mixed
    {
        $rules = Arr::toArray($this->prototypeGet('domain_rules', []));

        if (func_num_args() === 1) {
            return $rules[$name] ?? null;
        }

        $rules[$name] = $this->prototypeSnapshotValue($value);

        return $this->prototypeSet('domain_rules', $rules, 'prototypes.domain.rule_changed');
    }

    /**
     * Return all named domain rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Arr::toArray($this->prototypeGet('domain_rules', []));
    }

    /**
     * Get or assign one named default value shared by the domain.
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function defaultValue(string $name, mixed $value = null): mixed
    {
        $defaults = Arr::toArray($this->prototypeGet('domain_defaults', []));

        if (func_num_args() === 1) {
            return $defaults[$name] ?? null;
        }

        $defaults[$name] = $this->prototypeSnapshotValue($value);

        return $this->prototypeSet('domain_defaults', $defaults, 'prototypes.domain.default_changed');
    }

    /**
     * Return all named domain defaults.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return Arr::toArray($this->prototypeGet('domain_defaults', []));
    }

    /**
     * Get the full domain state bag, one value, or assign one value.
     *
     * @param string|null $name
     * @param mixed $value
     * @return mixed
     */
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

    /**
     * Return a normalized domain context payload for downstream consumers.
     *
     * @return array<string, mixed>
     */
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
