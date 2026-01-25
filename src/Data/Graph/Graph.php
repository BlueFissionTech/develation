<?php

namespace BlueFission\Data\Graph;

use BlueFission\Arr;
use BlueFission\Obj;
use BlueFission\DataTypes;
use BlueFission\DevElation as Dev;
use BlueFission\Behavioral\Behaviors\Event;

class Graph extends Obj
{
    protected $_data = [
        'nodes' => [],
        'edges' => [],
        'directed' => true,
        'meta' => [],
    ];

    protected $_types = [
        'nodes' => DataTypes::ARRAY,
        'edges' => DataTypes::ARRAY,
        'directed' => DataTypes::BOOLEAN,
        'meta' => DataTypes::ARRAY,
    ];

    protected $_lockDataType = true;

    public function __construct(array $graph = [], bool $directed = true)
    {
        parent::__construct();

        $this->exposeValueObject(true);
        $this->nodes->constraint(function (&$value): bool {
            $value = Arr::toArray($value);
            return true;
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

        $this->nodes = [];
        $this->edges = [];
        $this->directed = $directed;
        $this->meta = [];

        $this->echo($this->nodeMap(), [Event::CHANGE]);
        $this->echo($this->edgeMap(), [Event::CHANGE]);

        $this->when(Event::CHANGE, function (): void {
            if (!($this->_data['nodes'] ?? null) instanceof Arr) {
                $this->_data['nodes'] = new Arr([]);
            }
            if (!($this->_data['edges'] ?? null) instanceof Arr) {
                $this->_data['edges'] = new Arr([]);
            }
            if (!Arr::is($this->meta)) {
                $this->meta = [];
            }
        });

        if (!empty($graph)) {
            $this->load($graph);
        }
    }

    public function addNode(Node $node): void
    {
        $node = Dev::apply('_node', $node);
        if (!$node instanceof Node) {
            return;
        }

        $this->nodeMap()->set($node->getName(), $node);
        $this->edgeMap()->set($node->getName(), $node->edges());

        $node->when(Event::CHANGE, function () use ($node): void {
            $this->syncNode($node);
        });

        $this->dispatch(Event::ITEM_ADDED, ['node' => $node]);
        $this->dispatch(Event::CHANGE, ['node' => $node]);
        Dev::do('_node_added', [$node, $this]);
    }

    public function node(string $id): ?Node
    {
        $node = $this->nodeMap()->get($id);
        return $node instanceof Node ? $node : null;
    }

    public function nodes(): array
    {
        return $this->nodeMap()->val();
    }

    public function connect(string $from, string $to, array $attributes = [], ?bool $directed = null): void
    {
        $edge = [
            'from' => $from,
            'to' => $to,
            'attributes' => Arr::toArray($attributes),
        ];
        $edge = Dev::apply('_connect', $edge);
        $edge = Arr::toArray($edge);
        $edge = Arr::merge([
            'from' => $from,
            'to' => $to,
            'attributes' => Arr::toArray($attributes),
        ], $edge);

        $fromNode = $this->ensureNode((string)$edge['from']);
        $toNode = $this->ensureNode((string)$edge['to']);

        $attributes = Arr::toArray($edge['attributes'] ?? []);
        $fromNode->addEdge($toNode->getName(), $attributes);
        $this->edgeMap()->set($fromNode->getName(), $fromNode->edges());

        if ($this->isUndirected($directed)) {
            $toNode->addEdge($fromNode->getName(), $attributes);
            $this->edgeMap()->set($toNode->getName(), $toNode->edges());
        }

        $this->dispatch(Event::ITEM_ADDED, ['edge' => $edge]);
        $this->dispatch(Event::CHANGE, ['edge' => $edge]);
        Dev::do('_edge_added', [$edge, $this]);
    }

    public function neighbors(string $id): array
    {
        $edges = $this->edgeMap()->get($id);
        if (!Arr::is($edges)) {
            return [];
        }

        return Arr::keys($edges);
    }

    public function edgeAttributes(string $from, string $to): ?array
    {
        $edges = $this->edgeMap()->get($from);
        if (!Arr::is($edges)) {
            return null;
        }

        if (!Arr::hasKey($edges, $to)) {
            return null;
        }

        $attributes = $edges[$to];
        if (!Arr::is($attributes)) {
            return ['weight' => $attributes];
        }

        return $attributes;
    }

    public function getEdgeAttributes(string $from, string $to): ?array
    {
        return $this->edgeAttributes($from, $to);
    }

    public function shortestPath(string $start, string $end, ?callable $fitnessFunction = null): array
    {
        $nodeNames = Arr::keys($this->nodeMap()->val());
        if (count($nodeNames) === 0) {
            $nodeNames = Arr::keys($this->edgeMap()->val());
        }

        if (count($nodeNames) === 0) {
            return [];
        }

        $fitnessFunction = $fitnessFunction ?? function (array $attributes): float {
            if (Arr::hasKey($attributes, 'weight')) {
                return (float)$attributes['weight'];
            }
            if (Arr::hasKey($attributes, 'cost')) {
                return (float)$attributes['cost'];
            }
            if (Arr::hasKey($attributes, 'distance')) {
                return (float)$attributes['distance'];
            }
            if (Arr::hasKey($attributes, 0) && is_numeric($attributes[0])) {
                return (float)$attributes[0];
            }
            return 1.0;
        };

        $distances = [];
        $previous = [];
        $unvisited = new Arr([]);

        foreach ($nodeNames as $name) {
            $distances[$name] = PHP_INT_MAX;
            $previous[$name] = null;
            $unvisited->set($name, true);
        }

        if (!Arr::hasKey($distances, $start) || !Arr::hasKey($distances, $end)) {
            return [];
        }

        $distances[$start] = 0;

        while ($unvisited->count() > 0) {
            $unvisitedArray = $unvisited->val();

            $closest = null;
            $closestDistance = PHP_INT_MAX;

            foreach ($unvisitedArray as $name => $_) {
                if ($distances[$name] < $closestDistance) {
                    $closestDistance = $distances[$name];
                    $closest = $name;
                }
            }

            if ($closest === null) {
                break;
            }

            if ($closest === $end) {
                $path = [];
                $current = $end;

                while ($current !== null) {
                    $path[] = $current;
                    $current = $previous[$current] ?? null;
                }

                return array_reverse($path);
            }

            if ($distances[$closest] === PHP_INT_MAX) {
                break;
            }

            $edges = $this->edgeMap()->get($closest);
            if (!Arr::is($edges)) {
                $edges = [];
            }

            foreach ($edges as $neighbor => $value) {
                if (!Arr::hasKey($unvisitedArray, $neighbor)) {
                    continue;
                }

                $attributes = Arr::is($value) ? $value : ['weight' => $value];
                $edgeCost = $fitnessFunction($attributes);
                if ($edgeCost < 0) {
                    continue;
                }

                $alt = $distances[$closest] + $edgeCost;

                if ($alt < ($distances[$neighbor] ?? PHP_INT_MAX)) {
                    $distances[$neighbor] = $alt;
                    $previous[$neighbor] = $closest;
                }
            }

            $unvisited->delete($closest);
        }

        return [];
    }

    public function load(array $graph): void
    {
        $graph = Dev::apply('_in', $graph);
        foreach ($graph as $from => $edges) {
            $this->ensureNode((string)$from);
            $edges = Arr::toArray($edges);
            foreach ($edges as $to => $attributes) {
                if (!Arr::is($attributes)) {
                    $attributes = ['weight' => $attributes];
                }
                $this->connect((string)$from, (string)$to, $attributes);
            }
        }
    }

    protected function ensureNode(string $id): Node
    {
        $node = $this->node($id);
        if ($node instanceof Node) {
            return $node;
        }

        $node = new Node($id);
        $this->addNode($node);
        return $node;
    }

    protected function syncNode(Node $node): void
    {
        $this->nodeMap()->set($node->getName(), $node);
        $this->edgeMap()->set($node->getName(), $node->edges());
    }

    protected function isUndirected(?bool $directed): bool
    {
        if ($directed === null) {
            return !$this->directed;
        }

        return !$directed;
    }

    protected function nodeMap(): Arr
    {
        $nodes = $this->_data['nodes'] ?? null;
        if (!$nodes instanceof Arr) {
            $nodes = new Arr([]);
            $this->_data['nodes'] = $nodes;
        }

        return $nodes;
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
