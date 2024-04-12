<?php
namespace BlueFission\Tests;

use BlueFission\Arr;
 
class ArrTest extends ValTest {
 
 	static $classname = 'BlueFission\Arr';
 	protected $object;

	public function setUp(): void
	{
		$this->value = ['foo', 'bar'];

		$this->object = new static::$classname('First Item');

		$this->blankObject = new static::$classname();
		$this->nullObject = new static::$classname(null);
		$this->emptyObject = new static::$classname([]);
		$this->zeroObject = new static::$classname(0);
		$this->valueObject = new static::$classname(['foo']);
	}

	public function testConstructionCreatesCountableIndex()
	{
		$this->assertEquals('First Item', $this->object[0]);
	}

	public function testAppendingWithBlankOffset()
	{
		$this->object[] = 'Second Item';
		$this->assertEquals('Second Item', $this->object[1]);
	}

	public function testAppendingWithNumericOffset()
	{
		$this->object[] = 'Second Item';
		$this->object[3] = 'Third Item';
		$this->assertEquals('Third Item', $this->object[3]);
	}

	public function testNumericIndicesArentAssociative()
	{
		$this->assertTrue($this->object->isIndexed());
		$this->assertFalse($this->object->isAssoc());
	}

	public function testAppendingWithAlphaOffset()
	{
		$this->object[] = 'Second Item';
		$this->object[3] = 'Third Item';
		$this->object['four'] = 'Fourth Item';
		$this->assertEquals('Fourth Item', $this->object['four']);
	}

	public function testMixedIndicesAreAssociative()
	{

		$this->object[] = 'Second Item';
		$this->object[3] = 'Third Item';
		$this->object['four'] = 'Fourth Item';
		
		$this->assertFalse($this->object->isIndexed());
		$this->assertTrue($this->object->isAssoc());
	}

	public function testsRemovesDuplicates()
	{
		$object = new static::$classname(['foo', 'bar', 'foo']);
		$object->unique();
		$this->assertEquals(['foo', 'bar'], $object->val());
	}

	public function testsRemovesDuplicatesCaseInsensitive()
	{
		$object = new static::$classname(['foo', 'bar', 'Foo']);
		$object->iUnique();
		$this->assertEquals(['foo', 'bar'], $object->val());
	}

	public function testSetsValue()
	{
		$object = new static::$classname();
		$object->val(['foo']);
		$this->assertEquals(['foo'], $object->val());
	}

	public function testClearsValue()
	{
		$object = new static::$classname(['foobar']);
		$object->clear();
		$this->assertNull($object->val());
	}

	public function testSetsReference()
	{
		$object = new static::$classname();
		$value = ['foo'];
		$object->ref($value);
		$this->assertEquals(['foo'], $object->val());
		$value = ['bar'];
		$this->assertEquals(['bar'], $object->val());
		$object->val(['foobar']);
		$this->assertEquals(['foobar'], $value);
	}

	public function testSnapshotsValue()
	{
		$object = new static::$classname(['foo']);
		$object->snapshot();
		$this->assertEquals(['foo'], $object->val());
		$object->val(['bar']);
		$this->assertEquals(['bar'], $object->val());
		$object->reset();
		$this->assertEquals(['foo'], $object->val());
	}

	public function testClearsSnapshot()
	{
		$object = new static::$classname(['foo']);
		$object->snapshot();
		$this->assertEquals(['foo'], $object->val());
		$object->clearSnapshot();
		$object->val(['bar']);
		$this->assertEquals(['bar'], $object->val());
		$object->reset();
		$this->assertEquals(null, $object->val());
	}

	public function testGetsDelta()
	{
		$object = new static::$classname(['foo']);
		// $object->snapshot();
		$this->assertEquals(['foo'], $object->val());
		$object->val(['bar']);
		$this->assertEquals(['bar'], $object->val());

		$this->assertEquals(['bar'], $object->delta());
	}

	public function testsTagsAndGroups()
	{
		// Tagging arrays
		$array1 = static::$classname::make(['red', 'orange', 'yellow'])->tag('light');
		$array3 = static::$classname::make(['magenta', 'cyan']); // Not tagged as light
		$array2 = static::$classname::make(['green', 'blue', 'violet'])->tag('light');

		// Retrieving all light values
		$colors = static::$classname::grp('light');

		$rainbow = [];
		foreach ($colors as $color) {
		    $rainbow = array_merge($rainbow, $color());
		}

		$this->assertEquals(['red', 'orange', 'yellow', 'green', 'blue', 'violet'], $rainbow);
	}

	public function testRecognizesBlankAsNull()
	{
		$falseResult = $this->blankObject->isNull();
		$trueResult = $this->blankObject->isNotNull();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testRecognizesNullAsNull()
	{
		$falseResult = $this->nullObject->isNull();
		$trueResult = $this->nullObject->isNotNull();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	public function testDoesntRecognizeEmptyAsNull()
	{
		$falseResult = $this->emptyObject->isNull();
		$trueResult = $this->emptyObject->isNotNull();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testDoesntRecognizeZeroAsNull()
	{
		$falseResult = $this->zeroObject->isNull();
		$trueResult = $this->zeroObject->isNotNull();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testDoesntRecognizeValueAsNull()
	{
		$falseResult = $this->valueObject->isNull();
		$trueResult = $this->valueObject->isNotNull();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	// Empty Test
	public function testRecognizesBlankAsEmpty()
	{
		$falseResult = $this->blankObject->isEmpty();
		$trueResult = $this->blankObject->isNotEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testRecognizesNullAsEmpty()
	{
		$falseResult = $this->nullObject->isEmpty();
		$trueResult = $this->nullObject->isNotEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	public function testRecognizesEmptyasEmpty()
	{
		$falseResult = $this->emptyObject->isEmpty();
		$trueResult = $this->emptyObject->isNotEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testDoesntRecognizeZeroAsEmpty()
	{
		$falseResult = $this->zeroObject->isNotEmpty();
		$trueResult = $this->zeroObject->isEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	public function testAccurateFalsiness()
	{
		$trueResult = $this->blankObject->isFalsy();
		$this->assertTrue( $trueResult );
	
		$trueResult = $this->nullObject->isFalsy();
		$this->assertTrue( $trueResult );
	
		$trueResult = $this->emptyObject->isFalsy();
		$this->assertTrue( $trueResult );
	
		$falseResult = $this->zeroObject->isFalsy();
		$this->assertFalse( $falseResult );
	}
	
	public function testRecognizesZeroAsEmptyStatically()
	{
		$falseResult = static::$classname::isNotEmpty(0);
		$trueResult = static::$classname::isEmpty(0);
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testDoesntRecognizeValueAsEmptyStatically()
	{
		for ($i = 1; $i<100; $i++) {
			$trueResult = static::$classname::isEmpty($i);
			$falseResult = static::$classname::isNotEmpty($i);
		
			$this->assertTrue( $trueResult );
			$this->assertFalse( $falseResult );
		}
	}
}