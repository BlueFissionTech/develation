<?php
namespace BlueFission\Tests;

use BlueFission\Obj;
use BlueFission\Str;
 
class ObjTest extends \PHPUnit\Framework\TestCase {
 
 	static $classname = 'BlueFission\Obj';
 	protected $object;
	
	public function setUp(): void
	{
		$this->object = new static::$classname();
	}

	public function testEvaluatesAsStringUsingType()
	{
		$this->assertEquals(static::$classname, "".$this->object."");
	}

	public function testUndefinedAccessReturnsNull()
	{
		$this->assertNull($this->object->testValue);
	}

	public function testAddsAndClearsUndefinedFields()
	{
		$this->object->testValue = true;
		$this->assertTrue($this->object->testValue);

		$this->object->clear();
		$this->assertEquals(null, $this->object->testValue);
	}

	public function testAssignImportsAssociativeArrays()
	{
		$this->object->assign(['name' => 'Ada', 'role' => 'Engineer']);

		$this->assertSame('Ada', $this->object->name);
		$this->assertSame('Engineer', $this->object->role);
	}

	public function testAssignRejectsNonAssociativeArrays()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->object->assign(['Ada', 'Engineer']);
	}

	public function testExposeValueObjectReturnsUnderlyingValueObject()
	{
		$object = new class extends Obj {
			protected $_types = ['name' => \BlueFission\DataTypes::STRING];
		};

		$object->field('name', 'Ada');
		$this->assertSame('Ada', $object->field('name'));

		$object->exposeValueObject();
		$this->assertInstanceOf(Str::class, $object->field('name'));
	}

	public function testToArrayAndToJsonExposeAssignedValues()
	{
		$this->object->assign(['name' => 'Ada', 'role' => 'Engineer']);

		$this->assertSame(['name' => 'Ada', 'role' => 'Engineer'], $this->object->toArray());
		$this->assertSame('{"name":"Ada","role":"Engineer"}', $this->object->toJson());
	}
}
