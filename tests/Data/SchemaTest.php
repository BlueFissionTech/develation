<?php
namespace BlueFission\Tests\Data;

use BlueFission\Data\Schema;
use BlueFission\Data\Schema\FieldDefinition;

class SchemaTest extends \PHPUnit\Framework\TestCase
{
    public function testAppliesDefaultsAndCasting()
    {
        $schema = new Schema([
            'name' => ['type' => 'string', 'required' => true],
            'age' => ['type' => 'int', 'default' => 0],
            'active' => ['type' => 'bool', 'default' => true],
            'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
        ], ['strict' => true]);

        $result = $schema->apply([
            'name' => 123,
            'age' => '42',
            'tags' => ['alpha', 9],
        ]);

        $this->assertSame('123', $result['name']);
        $this->assertSame(42, $result['age']);
        $this->assertSame(true, $result['active']);
        $this->assertSame(['alpha', '9'], $result['tags']);
        $this->assertEmpty($schema->errors());
    }

    public function testBooleanCastingUsesStringLiterals()
    {
        $schema = new Schema([
            'enabled' => ['type' => 'bool'],
        ]);

        $result = $schema->transform(['enabled' => 'false']);

        $this->assertSame(false, $result['enabled']);
    }

    public function testValidationErrorsAndStrictUnknowns()
    {
        $metaSchema = new Schema([
            'title' => ['type' => 'string', 'required' => true],
        ]);

        $schema = new Schema([
            'id' => ['type' => 'integer', 'required' => true],
            'meta' => ['schema' => $metaSchema],
        ], ['strict' => true]);

        $valid = $schema->validate([
            'meta' => ['title' => null],
            'extra' => true,
        ]);

        $errors = $schema->errors();

        $this->assertFalse($valid);
        $this->assertArrayHasKey('id', $errors);
        $this->assertArrayHasKey('meta', $errors);
        $this->assertArrayHasKey('extra', $errors);
    }

    public function testConstraintFailure()
    {
        $schema = new Schema([
            'name' => new FieldDefinition('name', [
                'type' => 'string',
                'constraints' => function (&$value) {
                    $value = trim((string)$value);
                    return $value !== '';
                },
            ]),
        ]);

        $schema->apply(['name' => '   ']);

        $errors = $schema->errors();
        $this->assertArrayHasKey('name', $errors);
    }
}
