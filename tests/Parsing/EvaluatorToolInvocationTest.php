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
}
