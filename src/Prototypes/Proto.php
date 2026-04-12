<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\Val;
use BlueFission\DevElation as Dev;
use BlueFission\Prototypes\Support\PrototypeTools;

trait Proto
{
    use PrototypeTools;

    public function protoId(?string $id = null): mixed
    {
        $this->prototypeBoot('proto');

        if (Val::isNull($id)) {
            return (string) $this->prototypeGet('id', '');
        }

        $id = Str::trim($id);
        return $this->prototypeSet('id', $id, 'prototypes.proto.id_set');
    }

    public function kind(?string $kind = null): mixed
    {
        $this->prototypeBoot('proto');

        if (Val::isNull($kind)) {
            return (string) $this->prototypeGet('kind', 'proto');
        }

        return $this->prototypeSet('kind', Str::trim($kind), 'prototypes.proto.kind_set');
    }

    public function name(?string $name = null): mixed
    {
        $this->prototypeBoot('proto');

        if (Val::isNull($name)) {
            return (string) $this->prototypeGet('name', '');
        }

        return $this->prototypeSet('name', Str::trim($name), 'prototypes.proto.name_set');
    }

    public function labels(?array $labels = null): mixed
    {
        $this->prototypeBoot('proto');

        if (Val::isNull($labels)) {
            return Arr::toArray($this->prototypeGet('labels', []));
        }

        return $this->prototypeSet('labels', Arr::toArray($labels), 'prototypes.proto.labels_set');
    }

    public function addLabel(string $label): static
    {
        $label = Str::trim($label);
        if ($label === '') {
            return $this;
        }

        return $this->prototypeAppend('labels', $label, true, 'prototypes.proto.label_added');
    }

    public function traits(?array $traits = null): mixed
    {
        $this->prototypeBoot('proto');

        if (Val::isNull($traits)) {
            return Arr::toArray($this->prototypeGet('traits', []));
        }

        return $this->prototypeSet('traits', Arr::toArray($traits), 'prototypes.proto.traits_set');
    }

    public function addTrait(string $trait, mixed $value = true): static
    {
        $trait = Str::trim($trait);
        if ($trait === '') {
            return $this;
        }

        return $this->prototypeAssocSet('traits', $trait, $value, 'prototypes.proto.trait_added');
    }

    public function stateValue(?string $key = null, mixed $value = null): mixed
    {
        $this->prototypeBoot('proto');
        $state = Arr::toArray($this->prototypeGet('state', []));

        if (Val::isNull($key)) {
            return $state;
        }

        if (func_num_args() === 1) {
            return $state[$key] ?? null;
        }

        $state[$key] = $this->prototypeSnapshotValue($value);

        return $this->prototypeSet('state', $state, 'prototypes.proto.state_changed');
    }

    public function property(string $name, mixed $value = null): mixed
    {
        $this->prototypeBoot('proto');
        $properties = Arr::toArray($this->prototypeGet('properties', []));

        if (func_num_args() === 1) {
            return $properties[$name] ?? null;
        }

        $properties[$name] = $this->prototypeSnapshotValue($value);

        return $this->prototypeSet('properties', $properties, 'prototypes.proto.property_changed');
    }

    public function properties(?array $properties = null): mixed
    {
        $this->prototypeBoot('proto');

        if (Val::isNull($properties)) {
            return Arr::toArray($this->prototypeGet('properties', []));
        }

        return $this->prototypeSet('properties', Arr::toArray($properties), 'prototypes.proto.properties_set');
    }

    public function measure(string $name, mixed $value = null): mixed
    {
        $this->prototypeBoot('proto');
        $measures = Arr::toArray($this->prototypeGet('measures', []));

        if (func_num_args() === 1) {
            return $measures[$name] ?? null;
        }

        $measures[$name] = $this->prototypeSnapshotValue($value);

        return $this->prototypeSet('measures', $measures, 'prototypes.proto.measure_changed');
    }

    public function measures(?array $measures = null): mixed
    {
        $this->prototypeBoot('proto');

        if (Val::isNull($measures)) {
            return Arr::toArray($this->prototypeGet('measures', []));
        }

        return $this->prototypeSet('measures', Arr::toArray($measures), 'prototypes.proto.measures_set');
    }

    public function relate(string $relation, mixed $target, array $meta = []): static
    {
        $relation = Str::trim($relation);
        if ($relation === '') {
            return $this;
        }

        $relations = Arr::toArray($this->prototypeGet('relations', []));
        $relations[$relation] = $relations[$relation] ?? [];
        $relations[$relation][] = [
            'target' => $this->prototypeSnapshotValue($target),
            'meta' => $this->prototypeSnapshotValue($meta),
        ];

        return $this->prototypeSet('relations', $relations, 'prototypes.proto.related');
    }

    public function relations(?string $relation = null): array
    {
        $relations = Arr::toArray($this->prototypeGet('relations', []));

        if (Val::isNull($relation)) {
            return $relations;
        }

        return Arr::toArray($relations[$relation] ?? []);
    }

    public function confidence(?float $confidence = null): mixed
    {
        if (Val::isNull($confidence)) {
            return (float) $this->prototypeGet('confidence', 0.0);
        }

        return $this->prototypeSet('confidence', $confidence, 'prototypes.proto.confidence_changed');
    }

    public function domain(mixed $domain = null): mixed
    {
        if (Val::isNull($domain)) {
            return $this->prototypeGet('domain');
        }

        return $this->prototypeSet('domain', $this->prototypeSnapshotValue($domain), 'prototypes.proto.domain_assigned');
    }

    public function position(mixed $position = null): mixed
    {
        if (Val::isNull($position)) {
            return Arr::toArray($this->prototypeGet('position', []));
        }

        return $this->prototypeSet('position', Arr::toArray($this->prototypeSnapshotValue($position)), 'prototypes.proto.position_set');
    }

    public function record(mixed $event, array $meta = []): static
    {
        $history = Arr::toArray($this->prototypeGet('history', []));
        $history[] = [
            'event' => $this->prototypeSnapshotValue($event),
            'meta' => $this->prototypeSnapshotValue($meta),
        ];

        return $this->prototypeSet('history', $history, 'prototypes.proto.history_recorded');
    }

    public function history(): array
    {
        return Arr::toArray($this->prototypeGet('history', []));
    }

    public function summary(?string $summary = null): mixed
    {
        if (Val::isNull($summary)) {
            return (string) $this->prototypeGet('summary', '');
        }

        return $this->prototypeSet('summary', $summary, 'prototypes.proto.summary_set');
    }

    public function snapshot(): array
    {
        $this->prototypeBoot('proto');

        $snapshot = [
            'id' => $this->protoId(),
            'kind' => $this->kind(),
            'name' => $this->name(),
            'labels' => $this->labels(),
            'traits' => $this->traits(),
            'state' => $this->stateValue(),
            'properties' => $this->properties(),
            'measures' => $this->measures(),
            'relations' => $this->relations(),
            'conditions' => Arr::toArray($this->prototypeGet('conditions', [])),
            'causes' => Arr::toArray($this->prototypeGet('causes', [])),
            'effects' => Arr::toArray($this->prototypeGet('effects', [])),
            'confidence' => $this->confidence(),
            'history' => $this->history(),
            'summary' => (string) $this->prototypeGet('summary', ''),
            'domain' => $this->prototypeSnapshotValue($this->prototypeGet('domain')),
            'position' => Arr::toArray($this->prototypeGet('position', [])),
            'collectives' => Arr::toArray($this->prototypeGet('collective_memberships', [])),
        ];

        foreach ([
            'blueprint',
            'archetype',
            'schema',
            'defaults',
            'constraints',
            'capabilities',
            'components',
            'substance',
            'materiality',
            'autonomy',
            'control',
            'goals',
            'criteria',
            'strategies',
            'lastDecision',
            'dimensions',
            'coordinates',
            'frame',
            'anchor',
            'domain_rules',
            'domain_defaults',
            'domain_members',
            'domain_subdomains',
            'domain_collectives',
            'collective_kind',
            'collective_members',
            'collective_rules',
            'collective_state',
            'collective_destiny',
        ] as $key) {
            if (Arr::hasKey($this->_data, $key)) {
                $snapshot[$key] = $this->prototypeSnapshotValue($this->_data[$key]);
            }
        }

        $snapshot = Dev::apply('prototypes.proto.snapshot', $snapshot);

        return $snapshot;
    }

    public function explain(): string
    {
        $snapshot = $this->snapshot();
        $counts = [
            'relations' => count($snapshot['relations'] ?? []),
            'conditions' => count($snapshot['conditions'] ?? []),
            'causes' => count($snapshot['causes'] ?? []),
            'effects' => count($snapshot['effects'] ?? []),
            'history' => count($snapshot['history'] ?? []),
        ];

        if (isset($snapshot['goals']) && Arr::is($snapshot['goals'])) {
            $counts['goals'] = count($snapshot['goals']);
        }

        if (isset($snapshot['domain_members']) && Arr::is($snapshot['domain_members'])) {
            $counts['members'] = count($snapshot['domain_members']);
        }

        if (isset($snapshot['collective_members']) && Arr::is($snapshot['collective_members'])) {
            $counts['members'] = count($snapshot['collective_members']);
        }

        $summary = sprintf(
            '%s[%s] relations=%d conditions=%d causes=%d effects=%d history=%d',
            $snapshot['kind'] ?: 'proto',
            $snapshot['id'] ?: ($snapshot['name'] ?: 'unidentified'),
            $counts['relations'],
            $counts['conditions'],
            $counts['causes'],
            $counts['effects'],
            $counts['history']
        );

        if (Arr::hasKey($counts, 'goals')) {
            $summary .= sprintf(' goals=%d', $counts['goals']);
        }

        if (Arr::hasKey($counts, 'members')) {
            $summary .= sprintf(' members=%d', $counts['members']);
        }

        $this->prototypeSet('summary', $summary, 'prototypes.proto.explained');

        return $summary;
    }
}
