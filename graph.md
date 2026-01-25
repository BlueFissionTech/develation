# Graph Utilities

`BlueFission\Data\Graph` provides small, reusable graph and node helpers for
adjacency maps, edge metadata, and simple pathfinding.

## Quick Start

```php
use BlueFission\Data\Graph\Graph;
use BlueFission\Data\Graph\Node;

$graph = new Graph([], true); // directed by default

$graph->addNode(new Node('a'));
$graph->addNode(new Node('b'));
$graph->connect('a', 'b', ['weight' => 2]);
$graph->connect('a', 'c', ['weight' => 1]);
$graph->connect('c', 'b', ['weight' => 1]);

$path = $graph->shortestPath('a', 'b', function (array $edge): int {
    return (int)($edge['weight'] ?? 1);
});
```

## Nodes and Edges

```php
use BlueFission\Data\Graph\Node;

$node = new Node('alpha');
$node->addEdge('beta', ['weight' => 3, 'label' => 'link']);

$node->getEdgeAttributes('beta'); // ['weight' => 3, 'label' => 'link']
```

## Directed vs Undirected

```php
$graph = new Graph([], false); // undirected
$graph->connect('a', 'b', ['weight' => 2]); // adds a->b and b->a
```

## Events and Hooks

Graph and Node dispatch behavioral events you can listen to:

```php
use BlueFission\Behavioral\Behaviors\Event;

$graph->when(new Event(Event::ITEM_ADDED), function () {
    // node or edge added
});
```

Filters and actions are available via `DevElation::apply()` and
`DevElation::do()` using the internal hook names:

- `_node`, `_connect`, `_edge`
- `_node_added`, `_edge_added`, `_edge_removed`
