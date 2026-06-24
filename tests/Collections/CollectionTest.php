<?php

namespace BlueFission\Tests\Collections;

use BlueFission\Collections\Collection;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    public static $classname = 'BlueFission\Collections\Collection';
    protected $object;

    public function setUp(): void
    {
        $this->object = new static::$classname();
    }

    public function testAssignmentOnCreation()
    {
        $array = array(
            'var1' => "I'm a variable",
            'var2' => "I'm a variable, too",
            'var3' => "I'm a variable as well",
            'var4' => "Guess what, I'm a variable",
        );

        $object = new Collection($array);

        $this->assertEquals("I'm a variable, too", $object['var2']);
    }

    public function testRetrievalOfValues()
    {
        $array = [
            'var1' => "I'm a variable",
            'var2' => "I'm a variable, too",
            'var3' => "I'm a variable as well",
            'var4' => "Guess what, I'm a variable",
        ];

        $object = new Collection($array);

        $this->assertEquals("I'm a variable", $object->get('var1'));

        $this->assertEquals("I'm a variable, too", $object['var2']);
    }

    public function testSettingValues()
    {
        $item = "New Item";
        $this->object->add($item, "item");

        $this->assertEquals("New Item", $this->object->get("item"));
    }

    public function testIsArrayTraversable()
    {
        $array = [
            'var1' => "I'm a variable",
            'var2' => "I'm a variable, too",
            'var3' => "I'm a variable as well",
            'var4' => "Guess what, I'm a variable",
        ];

        $object = new Collection($array);

        $i = 0;
        foreach ($object as $a => $b) {
            $i++;
        }

        $this->assertEquals(4, $i);
    }

    public function testFilterUsesValueCallback()
    {
        $object = new Collection([1, 2, 3]);

        $filtered = $object->filter(fn ($value) => $value > 1);

        $this->assertEquals([1 => 2, 2 => 3], $filtered->toArray());
    }

    public function testFilterUsesValueAndKeyCallback()
    {
        $object = new Collection([
            'first' => 1,
            'second' => 2,
            'third' => 3,
        ]);

        $filtered = $object->filter(fn ($value, $key) => $value > 1 && $key !== 'second');

        $this->assertEquals(['third' => 3], $filtered->toArray());
    }

    public function testMapUsesValueCallbackAndPreservesKeys()
    {
        $object = new Collection([
            'first' => 'alpha',
            'second' => 'beta',
        ]);

        $mapped = $object->map('strtoupper');

        $this->assertSame(['first' => 'ALPHA', 'second' => 'BETA'], $mapped->toArray());
    }

    public function testMapUsesValueAndKeyCallback()
    {
        $object = new Collection([
            'first' => 1,
            'second' => 2,
        ]);

        $mapped = $object->map(fn ($value, $key) => $key . ':' . ($value * 2));

        $this->assertSame(['first' => 'first:2', 'second' => 'second:4'], $mapped->toArray());
    }
}
