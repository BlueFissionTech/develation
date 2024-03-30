<?php
namespace BlueFission\Tests;

use BlueFission\DevValue;
 
class DevValueTest extends \PHPUnit\Framework\TestCase {
 
 	static $classname = '\BlueFission\DevValue';
	public function setup()
	{
		$this->blankObject = new static::$classname;
		$this->nullObject = new static::$classname(null);
		$this->emptyObject = new static::$classname("");
		$this->zeroObject = new static::$classname(0);
		$this->valueObject = new static::$classname(1);
	}

	// public function tearDown() {
	// 	//... clean up here
	// }
	
	// Null
	public function testRecognizesBlankAsNull()
	{
		$trueResult = $this->blankObject->isNull();
		$falseResult = $this->blankObject->isNotNull();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testRecognizesNullAsNull()
	{
		$trueResult = $this->nullObject->isNull();
		$falseResult = $this->nullObject->isNotNull();
	
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
		$trueResult = $this->blankObject->isEmpty();
		$falseResult = $this->blankObject->isNotEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testRecognizesNullAsEmpty()
	{
		$trueResult = $this->nullObject->isEmpty();
		$falseResult = $this->nullObject->isNotEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	public function testRecognizesEmptyasEmpty()
	{
		$trueResult = $this->emptyObject->isEmpty();
		$falseResult = $this->emptyObject->isNotEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testDoesntRecognizeZeroAsEmpty()
	{
		$trueResult = $this->zeroObject->isNotEmpty();
		$falseResult = $this->zeroObject->isEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testDoesntRecognizeValueAsEmpty()
	{
		$falseResult = $this->valueObject->isEmpty();
		$trueResult = $this->valueObject->isNotEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	// Static Null Test
	public function testRecognizesBlankAsNullStatically()
	{
		$trueResult = DevValue::isNull();
		$falseResult = DevValue::isNotNull();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testRecognizesNullAsNullStatically()
	{
		$trueResult = DevValue::isNull(null);
		$falseResult = DevValue::isNotNull(null);
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	public function testRecognizesEmptyasNullStatically()
	{
		$trueResult = DevValue::isNotNull("");
		$falseResult = DevValue::isNull("");
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testDoesntRecognizeZeroAsNullStatically()
	{
		$falseResult = DevValue::isNull(0);
		$trueResult = DevValue::isNotNull(0);
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testDoesntRecognizeValueAsNullStatically()
	{
		for ($i = 1; $i<100; $i++) {
			$falseResult = DevValue::isNull($i);
			$trueResult = DevValue::isNotNull($i);
		
			$this->assertTrue( $trueResult );
			$this->assertFalse( $falseResult );
		}
	}

	public function testAccurateFalsiness()
	{
		$trueResult = $this->blankObject->isFalsy();
		$this->assertTrue( $trueResult );
	
		$trueResult = $this->nullObject->isFalsy();
		$this->assertTrue( $trueResult );
	
		$trueResult = $this->emptyObject->isFalsy();
		$this->assertTrue( $trueResult );
	
		$trueResult = $this->zeroObject->isFalsy();
		$this->assertTrue( $trueResult );
	}

	public function testAccurateTruthiness()
	{
		$trueResult = $this->valueObject->isTruthy();
		$this->assertTrue( $trueResult );
	}

	// Static Empty Test
	public function testRecognizesBlankAsEmptyStatically()
	{
		$trueResult = static::$classname::isEmpty();
		$falseResult = static::$classname::isNotEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testRecognizesNullAsEmptyStatically()
	{
		$trueResult = static::$classname::isEmpty(null);
		$falseResult = static::$classname::isNotEmpty(null);
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	public function testRecognizesEmptyasEmptyStatically()
	{
		$trueResult = static::$classname::isEmpty("");
		$falseResult = static::$classname::isNotEmpty("");
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testRecognizesZeroAsEmptyStatically()
	{
		$trueResult = static::$classname::isNotEmpty(0);
		$falseResult = static::$classname::isEmpty(0);
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testDoesntRecognizeValueAsEmptyStatically()
	{
		for ($i = 1; $i<100; $i++) {
			$falseResult = static::$classname::isEmpty($i);
			$trueResult = static::$classname::isNotEmpty($i);
		
			$this->assertTrue( $trueResult );
			$this->assertFalse( $falseResult );
		}
	}

 
 	// public function testIsSingleton()
 	// {
 	// 	$this->assertClassHasStaticAttribute('_instance', static::$classname);
 	// }

 	// /** 
 	//  * @expectedException InvalidArgumentException
 	//  */
 	// public function testThrowsException()
 	// {
 	// 	$this->object->dispatch('InvalidBehavior');
 	// }

 	/*
 	assertArrayHasKey
 	assertFileExists
 	expectOutputString
 	*/
}