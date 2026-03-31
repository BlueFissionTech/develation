<?php

namespace BlueFission\Tests\Parsing;

use BlueFission\Parsing\Contracts\IToolFunction;
use BlueFission\Parsing\Element;
use BlueFission\Parsing\Evaluator;
use BlueFission\Parsing\Registry\FunctionRegistry;
use BlueFission\Parsing\Registry\GeneratorRegistry;
use PHPUnit\Framework\TestCase;

class EvaluatorToolInvocationTest extends TestCase
{
    public function testParseParametersHandlesNullAndFalsyResolvedValuesSafely(): void
    {
        $element = new Element('root', '', '', []);
        $element->setScopeVariable('maybeNull', null);
        $element->setScopeVariable('zero', 0);
        $element->setScopeVariable('disabled', false);

        $evaluator = new class($element) extends Evaluator {
            public function parsePublic(?string $params): array
            {
                return $this->parseParameters($params);
            }
        };

        $this->assertSame([], $evaluator->parsePublic(null));
        $this->assertSame([null, 0, false], $evaluator->parsePublic('maybeNull, zero, disabled'));
    }

    public function testToolInvocationAssignsVariable(): void
    {
        FunctionRegistry::register(new class implements IToolFunction {
            public function name(): string
            {
                return 'fetchWeather';
            }

            public function execute(array $args): mixed
            {
                $city = $args[0] ?? 'unknown';

                return $city . ' forecast';
            }
        });

        $element = new Element('root', '', '', []);
        $evaluator = new Evaluator($element);

        $result = $evaluator->evaluate('fetchWeather("Nairobi") -> weatherData');

        $this->assertSame('Nairobi forecast', $result);
        $this->assertSame('Nairobi forecast', $element->getScopeVariable('weatherData'));
    }

    public function testToolInvocationCanAssignStructuredValues(): void
    {
        FunctionRegistry::register(new class implements IToolFunction {
            public function name(): string
            {
                return 'arrTool';
            }

            public function execute(array $args): mixed
            {
                return ['a' => 1, 'b' => 2];
            }
        });

        $element = new Element('root', '', '', []);
        $evaluator = new Evaluator($element);

        $result = $evaluator->evaluate('arrTool() -> data');

        $this->assertSame(['a' => 1, 'b' => 2], $result);
        $this->assertSame(['a' => 1, 'b' => 2], $element->getScopeVariable('data'));
        $this->assertSame(1, $element->resolveValue('data.a'));
    }

    public function testEvaluateWithoutGeneratorFailsPredictably(): void
    {
        $reflection = new \ReflectionClass(GeneratorRegistry::class);
        $property = $reflection->getProperty('generator');
        $property->setAccessible(true);
        $property->setValue(null, null);

        $element = new Element('root', '', '', []);
        $evaluator = new Evaluator($element);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("No generator registered for expression 'headline'.");

        $evaluator->evaluate('headline');
    }

    public function testSourceAssignmentSupportsStructuredData(): void
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . '_tmp';
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $file = $dir . DIRECTORY_SEPARATOR . 'source_' . uniqid() . '.json';
        file_put_contents($file, '{"a":1,"b":{"title":"Loaded"}}');

        $element = new Element('eval', '', '', [
            'expression' => 'data',
            'src' => $file,
        ]);
        $evaluator = new Evaluator($element);

        $result = $evaluator->evaluate('data');

        $this->assertSame(['a' => 1, 'b' => ['title' => 'Loaded']], $result);
        $this->assertSame('Loaded', $element->resolveValue('data.b.title'));
    }
}
