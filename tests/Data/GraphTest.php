<?php
namespace BlueFission\Tests\Data;

use BlueFission\Data\Graph\Graph;
use BlueFission\Data\Graph\Node;
use BlueFission\Behavioral\Behaviors\Event;

class GraphTest extends \PHPUnit\Framework\TestCase
{
    public function testAddNodeFiresItemAdded()
    {
        $graph = new Graph();
        $added = false;

        $graph->when(new Event(Event::ITEM_ADDED), function () use (&$added) {
            $added = true;
        });

        $graph->addNode(new Node('alpha'));

        $this->assertTrue($added);
        $this->assertInstanceOf(Node::class, $graph->node('alpha'));
    }

    public function testConnectAddsEdgesAndNeighbors()
    {
        $graph = new Graph();
        $graph->connect('a', 'b', ['weight' => 2]);

        $this->assertSame(['b'], $graph->neighbors('a'));
        $this->assertSame(['weight' => 2], $graph->edgeAttributes('a', 'b'));
    }

    public function testShortestPathUsesFitnessFunction()
    {
        $graph = new Graph();
        $graph->connect('a', 'b', ['weight' => 5]);
        $graph->connect('a', 'c', ['weight' => 1]);
        $graph->connect('c', 'b', ['weight' => 1]);

        $path = $graph->shortestPath('a', 'b', function (array $attributes): int {
            return (int)($attributes['weight'] ?? 0);
        });

        $this->assertSame(['a', 'c', 'b'], $path);
    }
}
