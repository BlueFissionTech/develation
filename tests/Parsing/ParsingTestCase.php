<?php
namespace BlueFission\Tests\Parsing;

use PHPUnit\Framework\TestCase;
use BlueFission\Parsing\Registry\TagRegistry;
use BlueFission\Parsing\Registry\RendererRegistry;
use BlueFission\Parsing\Registry\ExecutorRegistry;
use BlueFission\Parsing\Registry\PreparerRegistry;
use BlueFission\Parsing\Registry\DatatypeRegistry;
use BlueFission\Parsing\Registry\ValidatorRegistry;
use BlueFission\Parsing\Registry\GeneratorRegistry;
use BlueFission\Parsing\Contracts\IGenerator;
use BlueFission\Parsing\Element;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\Dispatches;

abstract class ParsingTestCase extends TestCase
{
    protected function registerParsingDefaults(): void
    {
        TagRegistry::registerDefaults();
        RendererRegistry::registerDefaults();
        ExecutorRegistry::registerDefaults();
        PreparerRegistry::registerDefaults();
        DatatypeRegistry::registerDefaults();
        ValidatorRegistry::registerDefaults();
        GeneratorRegistry::set(new StubGenerator());
    }
}

class StubGenerator implements IGenerator, IDispatcher
{
    use Dispatches {
        Dispatches::__construct as private __dispatchConstruct;
    }

    public function __construct()
    {
        $this->__dispatchConstruct();
    }

    public function generate(Element $element): string
    {
        return 'generated';
    }

    public function setDriver($driver): void
    {
        // No-op for test generator.
    }
}
