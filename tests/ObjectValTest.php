<?php

namespace BlueFission\Tests;

use BlueFission\DataTypes;
use BlueFission\IVal;
use BlueFission\Obj;
use BlueFission\ObjectVal;
use BlueFission\Str;
use BlueFission\ValFactory;
use PHPUnit\Framework\TestCase;

class ObjectValTest extends TestCase
{
    public function testFactoryUsesAnIValForObjectsWithoutChangingOtherMappings(): void
    {
        $value = (object)['name' => 'Ada'];
        $objectValue = ValFactory::make(DataTypes::OBJECT, $value);

        $this->assertInstanceOf(IVal::class, $objectValue);
        $this->assertInstanceOf(ObjectVal::class, $objectValue);
        $this->assertSame($value, $objectValue->val());
        $this->assertInstanceOf(Str::class, ValFactory::make(DataTypes::STRING, 'Ada'));
    }

    public function testObjectValueKeepsStaticAndFluentValueIdioms(): void
    {
        $first = (object)['name' => 'Ada'];
        $second = (object)['name' => 'Grace'];
        $value = ObjectVal::make($first);

        $this->assertInstanceOf(ObjectVal::class, $value);
        $this->assertTrue($value->isValid($second));
        $this->assertFalse($value->isValid('not an object'));
        $this->assertFalse($value->isValid(null));
        $this->assertSame($value, $value->val($second));
        $this->assertSame($second, $value->val());
    }

    public function testLockedObjectFieldAcceptsObjectsAndRejectsScalarsAndNull(): void
    {
        $first = (object)['name' => 'Ada'];
        $second = (object)['name' => 'Grace'];
        $object = new class extends Obj {
            protected $_data = [];
            protected $_types = ['payload' => DataTypes::OBJECT];
            protected $_lockDataType = true;
        };

        $object->payload = $first;
        $this->assertSame($first, $object->payload);
        $object->field('payload', $second);
        $this->assertSame($second, $object->payload);

        foreach (['not an object', 7, null] as $invalid) {
            $rejected = false;
            try {
                $object->field('payload', $invalid);
            } catch (\Exception $exception) {
                $rejected = true;
            }
            $this->assertTrue($rejected);
            $this->assertSame($second, $object->payload);
        }
    }

    public function testDeclaredNullableObjectFieldSupportsAssignmentAndReset(): void
    {
        $object = new class extends Obj {
            protected $_data = ['payload' => null];
            protected $_types = ['payload' => DataTypes::OBJECT];
            protected $_lockDataType = true;
        };

        $this->assertNull($object->payload);
        $value = (object)['name' => 'Ada'];
        $object->payload = $value;
        $this->assertSame($value, $object->payload);
        $object->field('payload', null);
        $this->assertNull($object->payload);
        $object->payload = $value;

        $field = $object->exposeValueObject()->field('payload');
        $this->assertInstanceOf(ObjectVal::class, $field);
        $field->reset();
        $this->assertNull($object->field('payload')->val());
    }
}
