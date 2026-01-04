<?php

namespace BlueFission\Tests;

use BlueFission\Val;
use BlueFission\DataTypes;

class ValTest extends \PHPUnit\Framework\TestCase
{
    public static $classname = '\BlueFission\Val';
    protected $value;
    protected $object;
    protected $blankObject;
    protected $nullObject;
    protected $emptyObject;
    protected $zeroObject;
    protected $valueObject;

    public function setUp(): void
    {
        $this->value = 1;
        $this->blankObject = new static::$classname();
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

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testRecognizesNullAsNull()
    {
        $trueResult = $this->nullObject->isNull();
        $falseResult = $this->nullObject->isNotNull();

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testDoesntRecognizeEmptyAsNull()
    {
        $falseResult = $this->emptyObject->isNull();
        $trueResult = $this->emptyObject->isNotNull();

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testDoesntRecognizeZeroAsNull()
    {
        $falseResult = $this->zeroObject->isNull();
        $trueResult = $this->zeroObject->isNotNull();

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testDoesntRecognizeValueAsNull()
    {
        $falseResult = $this->valueObject->isNull();
        $trueResult = $this->valueObject->isNotNull();

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    // Empty Test
    public function testRecognizesBlankAsEmpty()
    {
        $trueResult = $this->blankObject->isEmpty();
        $falseResult = $this->blankObject->isNotEmpty();

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testRecognizesNullAsEmpty()
    {
        $trueResult = $this->nullObject->isEmpty();
        $falseResult = $this->nullObject->isNotEmpty();

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testRecognizesEmptyasEmpty()
    {
        $trueResult = $this->emptyObject->isEmpty();
        $falseResult = $this->emptyObject->isNotEmpty();

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testDoesntRecognizeZeroAsEmpty()
    {
        $trueResult = $this->zeroObject->isNotEmpty();
        $falseResult = $this->zeroObject->isEmpty();

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testDoesntRecognizeValueAsEmpty()
    {
        $falseResult = $this->valueObject->isEmpty();
        $trueResult = $this->valueObject->isNotEmpty();

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    // Static Null Test
    public function testRecognizesBlankAsNullStatically()
    {
        $trueResult = Val::isNull();
        $falseResult = Val::isNotNull();

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testRecognizesNullAsNullStatically()
    {
        $trueResult = Val::isNull(null);
        $falseResult = Val::isNotNull(null);

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testRecognizesEmptyasNullStatically()
    {
        $trueResult = Val::isNotNull("");
        $falseResult = Val::isNull("");

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testDoesntRecognizeZeroAsNullStatically()
    {
        $falseResult = Val::isNull(0);
        $trueResult = Val::isNotNull(0);

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testDoesntRecognizeValueAsNullStatically()
    {
        for ($i = 1; $i < 100; $i++) {
            $falseResult = Val::isNull($i);
            $trueResult = Val::isNotNull($i);

            $this->assertTrue($trueResult);
            $this->assertFalse($falseResult);
        }
    }

    public function testAccurateFalsiness()
    {
        $trueResult = $this->blankObject->isFalsy();
        $this->assertTrue($trueResult);

        $trueResult = $this->nullObject->isFalsy();
        $this->assertTrue($trueResult);

        $trueResult = $this->emptyObject->isFalsy();
        $this->assertTrue($trueResult);

        $trueResult = $this->zeroObject->isFalsy();
        $this->assertTrue($trueResult);
    }

    public function testAccurateTruthiness()
    {
        $trueResult = $this->valueObject->isTruthy();
        $this->assertTrue($trueResult);
    }

    // Static Empty Test
    public function testRecognizesBlankAsEmptyStatically()
    {
        $trueResult = static::$classname::isEmpty();
        $falseResult = static::$classname::isNotEmpty();

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testRecognizesNullAsEmptyStatically()
    {
        $trueResult = static::$classname::isEmpty(null);
        $falseResult = static::$classname::isNotEmpty(null);

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testRecognizesEmptyasEmptyStatically()
    {
        $trueResult = static::$classname::isEmpty("");
        $falseResult = static::$classname::isNotEmpty("");

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testRecognizesZeroAsEmptyStatically()
    {
        $trueResult = static::$classname::isNotEmpty(0);
        $falseResult = static::$classname::isEmpty(0);

        $this->assertTrue($trueResult);
        $this->assertFalse($falseResult);
    }

    public function testDoesntRecognizeValueAsEmptyStatically()
    {
        for ($i = 1; $i < 100; $i++) {
            $falseResult = static::$classname::isEmpty($i);
            $trueResult = static::$classname::isNotEmpty($i);

            $this->assertTrue($trueResult);
            $this->assertFalse($falseResult);
        }
    }

    public function testCastsAsType()
    {
        $object = new static::$classname();
        $value = $object->cast()->val();
        if ($object->getType() && DataTypes::GENERIC->value !== $object->getType()) {
            $this->assertTrue(static::$classname::isValid($value));
        } else {
            $this->assertTrue(static::$classname::isValid($value));
        }


        $object = new static::$classname(true);
        if ($object->getType() && DataTypes::GENERIC->value !== $object->getType()) {
            $value = $object->cast()->val();
            $this->assertEquals($object->getType(), gettype($value));
            $this->assertTrue(static::$classname::isValid($value));
        }

        $object = new static::$classname(1);
        if ($object->getType() && DataTypes::GENERIC->value !== $object->getType()) {
            $value = $object->cast()->val();
            $this->assertEquals($object->getType(), gettype($value));
            $this->assertTrue(static::$classname::isValid($value));
        }

        $object = new static::$classname('Hello, World');
        if ($object->getType() && DataTypes::GENERIC->value !== $object->getType()) {
            $value = $object->cast()->val();
            $this->assertEquals($object->getType(), gettype($value));
            $this->assertTrue(static::$classname::isValid($value));
        }

        $object = new static::$classname(1.1);
        if ($object->getType() && DataTypes::GENERIC->value !== $object->getType()) {
            $value = $object->cast()->val();
            $this->assertEquals($object->getType(), gettype($value));
            $this->assertTrue(static::$classname::isValid($value));
        }

        $object = new static::$classname(['foobar']);
        if ($object->getType() && DataTypes::GENERIC->value !== $object->getType()) {
            $value = $object->cast()->val();
            $this->assertEquals($object->getType(), gettype($value));
            $this->assertTrue(static::$classname::isValid($value));
        }
    }

    public function testsGrab()
    {
        $var1 = $this->value;
        $var2 = null;

        if (static::$classname::is($var1)) {
            $var2 = static::$classname::grab();
        }

        $this->assertEquals($var1, $var2);
    }

    public function testsUse()
    {
        $var1 = $this->value;
        $var2 = null;

        if (static::$classname::is($var1)) {
            $var2 = static::$classname::use();
        }

        $this->assertEquals($var1, $var2->val());
    }
}
