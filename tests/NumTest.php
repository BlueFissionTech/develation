<?php

namespace BlueFission\Tests;

use BlueFission\Num;

class NumTest extends ValTest
{
    public static $classname = 'BlueFission\Num';
    protected $object;
    protected $blankObject;
    protected $zeroObject;
    protected $integerObject;
    protected $largeObject;

    public function setUp(): void
    {
        $this->value = 42;

        $this->blankObject = new static::$classname();
        $this->zeroObject = new static::$classname(0);
        $this->integerObject = new static::$classname(1);
        $this->largeObject = new static::$classname(29);
        $this->nullObject = new static::$classname(null);
        $this->emptyObject = new static::$classname("");
        $this->valueObject = new static::$classname(2.5);
    }

    // public function tearDown() {
    // 	//... clean up here
    // }

    // Default
    public function testValueReturnsAsNumeric()
    {
        $object = new Num('letters');
        $this->assertFalse(is_numeric($object->val()));
        $this->assertTrue(is_numeric($object()));
        // $this->assertTrue(is_numeric($object->cast()->val()));
    }

    public function testZeroAsValidNumber()
    {
        $this->assertTrue($this->zeroObject->isValid());
        $this->assertFalse($this->blankObject->isValid());
    }

    public function testPercentageReturnsCorrectValue()
    {
        $this->assertEquals(0.058, $this->largeObject->percentage(5));
    }

    public function testSetsValue()
    {
        $object = new static::$classname();
        $object->val(1);
        $this->assertEquals(1, $object->val());
    }

    public function testClearsValue()
    {
        $object = new static::$classname(1);
        $object->clear();
        $this->assertNull($object->val());
    }

    public function testSetsReference()
    {
        $object = new static::$classname();
        $value = 1;
        $object->ref($value);
        $this->assertEquals(1, $object->val());
        $value = 2;
        $this->assertEquals(2, $object->val());
        $object->val(3);
        $this->assertEquals(3, $value);
    }

    public function testSnapshotsValue()
    {
        $object = new static::$classname(1);
        $object->snapshot();
        $this->assertEquals(1, $object->val());
        $object->val(2);
        $this->assertEquals(2, $object->val());
        $object->reset();
        $this->assertEquals(1, $object->val());
    }

    public function testClearsSnapshot()
    {
        $object = new static::$classname(1);
        $object->snapshot();
        $this->assertEquals(1, $object->val());
        $object->clearSnapshot();
        $object->val(2);
        $this->assertEquals(2, $object->val());
        $object->reset();
        $this->assertEquals(null, $object->val());
    }

    public function testGetsDelta()
    {
        $object = new static::$classname(1);
        $object->snapshot();
        $this->assertEquals(1, $object->val());
        $object->val(2);
        $this->assertEquals(2, $object->val());
        $this->assertEquals(1, $object->delta());
    }

    public function testsTagsAndGroups()
    {
        // Tagging numbers
        $number1 = static::$classname::make(10)->tag('prime');
        $number2 = static::$classname::make(20)->tag('prime');
        $number3 = static::$classname::make(30); // Not tagged as prime

        // Retrieving all prime numbers
        $primes = static::$classname::grp('prime');

        $amount = 0;
        foreach ($primes as $prime) {
            $amount += $prime(); // Outputs 10 and 20, but not 30
        }

        $this->assertEquals(30, $amount);
    }

    public function testRomanNumerals()
    {
        $roman = Num::rom(2012);
        $this->assertEquals('MMXII', $roman);

        $roman = Num::rom(756);
        $this->assertEquals('DCCLVI', $roman);
    }
}
