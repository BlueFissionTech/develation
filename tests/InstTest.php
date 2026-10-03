<?php

namespace BlueFission\Tests;

use BlueFission\DataTypes;
use BlueFission\IVal;
use BlueFission\Inst;
use BlueFission\Obj;
use BlueFission\Ref;
use BlueFission\ValFactory;
use PHPUnit\Framework\TestCase;

class InstTest extends TestCase
{
    public function testFactoryUsesInstForObjectsWithoutChangingOtherMappings(): void
    {
        $value = (object)['name' => 'Ada'];
        $inst = ValFactory::make(DataTypes::OBJECT, $value);

        $this->assertInstanceOf(IVal::class, $inst);
        $this->assertInstanceOf(Inst::class, $inst);
        $this->assertSame($value, $inst->val());
    }

    public function testFactoryUsesRefForResources(): void
    {
        $handle = fopen('php://memory', 'w+');
        try {
            $ref = ValFactory::make(DataTypes::RESOURCE, $handle);
            $this->assertInstanceOf(Ref::class, $ref);
            $this->assertSame($handle, $ref->val());
        } finally {
            fclose($handle);
        }
    }

    public function testInstBuildsAnEmptyObjectWhenNoInputIsProvided(): void
    {
        $inst = Inst::make();

        $this->assertInstanceOf(Inst::class, $inst);
        $this->assertTrue($inst->is());
        $this->assertInstanceOf(\stdClass::class, $inst->val());
    }

    public function testInstAcceptsArrayAndJsonInputAndConvertsItToObject(): void
    {
        $arrayInst = Inst::make(['name' => 'Ada', 'age' => 42]);
        $jsonInst = Inst::make('{"name":"Grace","language":"COBOL"}');

        $this->assertInstanceOf(\stdClass::class, $arrayInst->val());
        $this->assertEquals('Ada', $arrayInst->val()->name);
        $this->assertEquals('Grace', $jsonInst->val()->name);
        $this->assertEquals('COBOL', $jsonInst->val()->language);
    }

    public function testInstConvertsWrappedObjectIntoObj(): void
    {
        $inst = Inst::make((object)['name' => 'Ada', 'language' => 'PHP']);
        $obj = $inst->convert();

        $this->assertInstanceOf(Obj::class, $obj);
        $this->assertEquals('Ada', $obj->field('name'));
        $this->assertEquals('PHP', $obj->field('language'));
    }

    public function testInstRespectsRefAndObjectValidation(): void
    {
        $ref = Ref::bind($value);
        $value = (object)['name' => 'Ada'];
        $inst = Inst::make($ref);

        $this->assertSame($value, $inst->val());
        $this->assertTrue($inst->isValid($value));
        $this->assertFalse($inst->isValid('not an object'));
    }

    public function testObjTypedFieldAcceptsInstAsObjectValue(): void
    {
        $value = (object)['name' => 'Ada'];
        $obj = new class extends Obj {
            protected $_data = [];
            protected $_types = ['payload' => DataTypes::OBJECT];
            protected $_lockDataType = true;
        };

        $obj->payload = $value;
        $this->assertSame($value, $obj->payload);

        foreach (['bad', 7, null] as $invalid) {
            $rejected = false;
            try {
                $obj->field('payload', $invalid);
            } catch (\Exception $exception) {
                $rejected = true;
            }
            $this->assertTrue($rejected);
        }
    }

    public function testDeclaredNullableObjectFieldCanReturnToNull(): void
    {
        $obj = new class extends Obj {
            protected $_data = ['payload' => null];
            protected $_types = ['payload' => DataTypes::OBJECT];
            protected $_lockDataType = true;
        };

        $this->assertNull($obj->payload);
        $value = (object)['name' => 'Ada'];
        $obj->payload = $value;
        $this->assertSame($value, $obj->payload);
        $obj->field('payload', null);
        $this->assertNull($obj->payload);

        $obj->field('payload', $value);
        $field = $obj->exposeValueObject()->field('payload');
        $this->assertInstanceOf(Inst::class, $field);
        $field->reset();
        $this->assertNull($field->val());
    }
}
