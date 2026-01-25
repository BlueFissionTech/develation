<?php

namespace BlueFission\Data\Graph;

use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\Obj;
use BlueFission\DataTypes;
use BlueFission\DevElation as Dev;
use BlueFission\Behavioral\Behaviors\Event;

class Node extends Obj
{
    protected $_data = [
        'id' => '',
        'edges' => [],
        'data' => null,
        'meta' => [],
    ];

    protected $_types = [
        'id' => DataTypes::STRING,
        'edges' => DataTypes::ARRAY,
        'data' => DataTypes::GENERIC,
        'meta' => DataTypes::ARRAY,
    ];

    protected $_lockDataType = true;

    public function __construct(string $id, $data = null, array $edges = [], array $meta = [])
    {
        parent::__construct();

        $this->exposeValueObject(true);
        $this->id->constraint(function (&$value): bool {
            $value = Str::trim((string)$value);
            return $value !== '';
        });
        $this->edges->constraint(function (&$value): bool {
            $value = Arr::toArray($value);
            return true;
        });
        $this->meta->constraint(function (&$value): bool {
            $value = Arr::toArray($value);
            return true;
        });
        $this->exposeValueObject(false);

        $this->id = $id;
        $this->data = Dev::apply('_in', $data);
        $this->edges = $edges;
        $this->meta = $meta;

        $this->when(Event::CHANGE, function (): void {
            if (!($this->_data['edges'] ?? null) instanceof Arr) {
                $this->_data['edges'] = new Arr([]);
            }
            if (!Arr::is($this->meta)) {
                $this->meta = [];
            }
        });
    }

    public function name(): string
    {
        return (string)$this->id;
    }

    public function getName(): string
    {
        return $this->name();
    }

    public function edges(): array
    {
        return Arr::toArray($this->edges);
    }

    public function getEdges(): array
    {
        return $this->edges();
    }

    public function hasEdge(string $nodeName): bool
    {
        $edges = $this->edgeMap()->val();
        return Arr::hasKey($edges, $nodeName);
    }

    public function addEdge(string $nodeName, array $attributes = []): self
    {
        $attributes = Arr::toArray($attributes);
        $attributes = Dev::apply('_edge', $attributes);
        $this->edgeMap()->set($nodeName, $attributes);
        $this->dispatch(Event::ITEM_ADDED, ['node' => $this->name(), 'edge' => $nodeName, 'attributes' => $attributes]);
        $this->dispatch(Event::CHANGE, ['node' => $this->name(), 'edge' => $nodeName]);
        Dev::do('_edge_added', [$this, $nodeName, $attributes]);

        return $this;
    }

    public function removeEdge(string $nodeName): self
    {
        $edges = $this->edgeMap();
        $edges->delete($nodeName);

        $this->dispatch(Event::DELETED, ['node' => $this->name(), 'edge' => $nodeName]);
        $this->dispatch(Event::CHANGE, ['node' => $this->name(), 'edge' => $nodeName]);
        Dev::do('_edge_removed', [$this, $nodeName]);

        return $this;
    }

    public function getEdgeAttributes(string $nodeName): ?array
    {
        $edges = $this->edgeMap()->val();
        if (!Arr::hasKey($edges, $nodeName)) {
            return null;
        }

        $attributes = $edges[$nodeName];
        if (!Arr::is($attributes)) {
            return ['weight' => $attributes];
        }

        return $attributes;
    }

    protected function edgeMap(): Arr
    {
        $edges = $this->_data['edges'] ?? null;
        if (!$edges instanceof Arr) {
            $edges = new Arr([]);
            $this->_data['edges'] = $edges;
        }

        return $edges;
    }
}
