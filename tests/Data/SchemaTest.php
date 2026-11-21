<?php
namespace BlueFission\Tests\Data;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Data\Schema;
use BlueFission\Data\Schema\FieldDefinition;
use BlueFission\IVal;
use BlueFission\Num;

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

    public function testRejectedNumericWrappersDoNotEmitConversionNotices(): void
    {
        $schema = new Schema([
            'value' => ['constraints' => [static fn ($value): bool => !$value instanceof IVal]],
        ], ['strict' => true]);
        $diagnostics = [];
        set_error_handler(static function (int $severity, string $message) use (&$diagnostics): bool {
            $diagnostics[] = [$severity, $message];
            return true;
        });

        try {
            foreach ([7, 1.25] as $number) {
                $wrapper = Num::make($number);
                $this->assertTrue($schema->validate(['value' => $number]));
                $this->assertSame([], $schema->errors());
                $this->assertFalse($schema->validate(['value' => $wrapper]));
                $this->assertNotEmpty($schema->errorsFor('value'));
                $this->assertSame($number, $wrapper->val());
            }
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $diagnostics);
    }

    public function testRepeatedWrapperRejectionClearsErrorsAfterNativeRecovery(): void
    {
        $schema = new Schema([
            'value' => ['constraints' => [static fn ($value): bool => !$value instanceof IVal]],
        ], ['strict' => true]);
        $wrapper = Num::make(1.25);
        $diagnostics = [];
        set_error_handler(static function (int $severity, string $message) use (&$diagnostics): bool {
            $diagnostics[] = [$severity, $message];
            return true;
        });

        try {
            for ($attempt = 0; $attempt < 2; $attempt++) {
                $this->assertFalse($schema->validate(['value' => $wrapper]));
                $this->assertNotEmpty($schema->errorsFor('value'));
                $this->assertSame(1.25, $wrapper->val());
            }
            $this->assertTrue($schema->validate(['value' => 1.25]));
            $this->assertSame([], $schema->errors());
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $diagnostics);
    }

    public function testWrapperRejectionAndRecoveryPreserveSchemaEvents(): void
    {
        $schema = new Schema([
            'value' => ['constraints' => [static fn ($value): bool => !$value instanceof IVal]],
        ], ['strict' => true]);
        $events = [];
        foreach ([Event::FAILURE, Event::SUCCESS, Event::PROCESSED] as $name) {
            $schema->when(new Event($name), static function () use (&$events, $name): void {
                $events[] = $name;
            });
        }

        $wrapper = Num::make(1.25);
        $this->assertFalse($schema->validate(['value' => $wrapper]));
        $this->assertNotEmpty($schema->errorsFor('value'));
        $this->assertTrue($schema->validate(['value' => 1.25]));
        $this->assertSame([], $schema->errors());
        $this->assertSame(1.25, $wrapper->val());
        $this->assertSame(
            [Event::FAILURE, Event::PROCESSED, Event::SUCCESS, Event::PROCESSED],
            $events
        );
    }

    public function testNumericWrapperConstraintKeepsCastingAndNestedValidationDistinct(): void
    {
        $rejectWrappers = static fn ($value): bool => !$value instanceof IVal;
        $plain = new Schema([
            'value' => ['type' => 'float', 'constraints' => [$rejectWrappers], 'nullable' => true],
        ], ['strict' => true]);
        $accept = new Schema(['value' => ['constraints' => [static fn ($value): bool => true]]]);
        $nested = new Schema(['record' => ['schema' => $plain]], ['strict' => true]);
        $diagnostics = [];
        set_error_handler(static function (int $severity, string $message) use (&$diagnostics): bool {
            $diagnostics[] = [$severity, $message];
            return true;
        });

        try {
            $this->assertTrue($accept->validate(['value' => Num::make(7)]));
            $this->assertSame([], $accept->errors());
            $this->assertTrue($plain->validate(['value' => null]));
            $this->assertSame([], $plain->errors());
            $this->assertFalse($nested->validate(['record' => ['value' => Num::make(1.25)]]));
            $this->assertNotEmpty($nested->errorsFor('record'));
            $this->assertSame(['value' => 1.25], $plain->transform(['value' => Num::make(1.25)]));
            $this->assertSame([], $plain->errors());
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $diagnostics);
    }

    public function testCallableArityUsesFuncForCallableReflection()
    {
        $schema = new Schema();
        $callable = new class {
            public function __invoke($value, $name, array $input)
            {
                return true;
            }
        };

        $this->assertSame(3, $this->invokeSchemaMethod($schema, 'callableArity', [$callable]));
        $this->assertSame(2, $this->invokeSchemaMethod($schema, 'callableArity', [[$this, 'schemaArityFixture']]));
        $this->assertSame(1, $this->invokeSchemaMethod($schema, 'callableArity', ['strlen']));
    }

    public function schemaArityFixture($value, $name)
    {
        return true;
    }

    public function testFieldDefinitionNormalizesDefaultsAndConstraints()
    {
        $constraint = function (&$value) {
            return $value !== null;
        };

        $definition = new FieldDefinition('score', [
            'type' => \BlueFission\DataTypes::INTEGER,
            'default' => null,
            'constraints' => $constraint,
        ]);

        $this->assertSame('integer', $definition->type());
        $this->assertTrue($definition->hasDefault());
        $this->assertNull($definition->defaultValue());
        $this->assertSame([$constraint], $definition->constraints());
    }

    private function invokeSchemaMethod(Schema $schema, string $method, array $arguments = [])
    {
        $reflection = new \ReflectionClass($schema);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($schema, $arguments);
    }
}
