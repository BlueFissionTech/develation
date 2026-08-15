<?php

namespace BlueFission\Tests\Parsing;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Preparers\BasePreparer;
use BlueFission\Parsing\Registry\PreparerRegistry;
use PHPUnit\Framework\TestCase;

class PreparerRegistryTest extends TestCase
{
    public function testRegisterDefaultsIsIdempotent(): void
    {
        PreparerRegistry::registerDefaults();
        $firstCount = count(PreparerRegistry::all());

        PreparerRegistry::registerDefaults();

        $this->assertSame($firstCount, count(PreparerRegistry::all()));
        $this->assertNotNull(PreparerRegistry::get('default.variable'));
        $this->assertNotNull(PreparerRegistry::get('default.path'));
        $this->assertNotNull(PreparerRegistry::get('default.hierarchy'));
        $this->assertNotNull(PreparerRegistry::get('default.event_bubble'));
    }

    public function testKeyedRegistrationReplacesWithoutMultiplyingExecution(): void
    {
        $first = new CountingPreparer();
        $replacement = new CountingPreparer();
        $initialCount = count(PreparerRegistry::all());

        PreparerRegistry::register($first, key: 'test.reader');
        PreparerRegistry::register($replacement, key: 'test.reader');

        $element = new Element('test', '', '');
        foreach (PreparerRegistry::all() as $preparer) {
            if ($preparer->supports($element)) {
                $preparer->prepare($element);
            }
        }

        $this->assertSame($initialCount + 1, count(PreparerRegistry::all()));
        $this->assertSame(0, $first->preparations);
        $this->assertSame(1, $replacement->preparations);
        $this->assertSame($replacement, PreparerRegistry::get('test.reader'));

        $this->assertTrue(PreparerRegistry::unregister('test.reader'));
        $this->assertFalse(PreparerRegistry::unregister('test.reader'));
    }

    public function testReleaseRemovesOnlyMatchingScopedRegistrations(): void
    {
        $persistent = new CountingPreparer();
        $persistentKey = PreparerRegistry::register($persistent);
        PreparerRegistry::register(new CountingPreparer(), key: 'test.scope.first', scope: 'test.scope');
        PreparerRegistry::register(new CountingPreparer(), key: 'test.scope.second', scope: 'test.scope');
        PreparerRegistry::register(new CountingPreparer(), key: 'test.other', scope: 'other.scope');

        $this->assertSame(2, PreparerRegistry::release('test.scope'));
        $this->assertSame($persistent, PreparerRegistry::get($persistentKey));
        $this->assertNull(PreparerRegistry::get('test.scope.first'));
        $this->assertNull(PreparerRegistry::get('test.scope.second'));
        $this->assertNotNull(PreparerRegistry::get('test.other'));

        PreparerRegistry::unregister($persistentKey);
        PreparerRegistry::unregister('test.other');
    }
}

class CountingPreparer extends BasePreparer
{
    public int $preparations = 0;

    public function prepare(Element $element): void
    {
        $this->preparations++;
    }
}
