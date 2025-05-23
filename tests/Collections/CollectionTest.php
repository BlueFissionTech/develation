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
}
