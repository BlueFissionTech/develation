<?php

namespace BlueFission\Tests\Parsing;

use BlueFission\Parsing\Contracts\IToolFunction;
use BlueFission\Parsing\Element;
use BlueFission\Parsing\Evaluator;
use BlueFission\Parsing\Registry\FunctionRegistry;
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
}
