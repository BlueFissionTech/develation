<?php

namespace BlueFission\Tests\Services;

use BlueFission\Services\Application;
use PHPUnit\Framework\TestCase;

class ApplicationNullableDependenciesTest extends TestCase
{
    public function testOmittedNullableClassAndInterfaceUseTheirNullDefaults(): void
    {
        $app = $this->application('omitted');

        self::assertNull($app->resolve(NullableConcreteConsumer::class)->dependency);
        self::assertNull($app->resolve(NullableInterfaceConsumer::class)->dependency);
    }

    public function testExplicitlyBoundNullIsNotReplacedByImplicitConstruction(): void
    {
        $app = $this->application('bound-null');
        $app->bindArgs(['dependency' => null], NullableConcreteConsumer::class);

        self::assertNull($app->resolve(NullableConcreteConsumer::class)->dependency);
    }

    public function testSuppliedObjectIsRetained(): void
    {
        $app = $this->application('supplied');
        $dependency = new NullableDependency();
        $app->bindArgs(['dependency' => $dependency], NullableConcreteConsumer::class);

        self::assertSame($dependency, $app->resolve(NullableConcreteConsumer::class)->dependency);
    }

    public function testExistingScalarNullFallbackRemainsUnchanged(): void
    {
        $app = $this->application('scalar');
        $app->bindArgs(['name' => null], ScalarDefaultConsumer::class);

        self::assertSame('default', $app->resolve(ScalarDefaultConsumer::class)->name);
    }

    public function testRequiredClassAndBoundInterfaceStillResolve(): void
    {
        $app = $this->application('required');
        $app->bind(NullableDependencyContract::class, NullableDependency::class);

        self::assertInstanceOf(NullableDependency::class, $app->resolve(RequiredConcreteConsumer::class)->dependency);
        self::assertInstanceOf(NullableDependency::class, $app->resolve(RequiredNullableConsumer::class)->dependency);
        self::assertInstanceOf(NullableDependency::class, $app->resolve(RequiredInterfaceConsumer::class)->dependency);
    }

    public function testMethodInvocationRespectsOmittedAndExplicitNull(): void
    {
        $app = $this->application('method');
        $target = new NullableMethodTarget();
        $invoke = new \ReflectionMethod(Application::class, 'executeServiceMethod');

        self::assertNull($invoke->invoke($app, [$target, 'acceptConcrete']));
        self::assertNull($invoke->invoke($app, [$target, 'acceptConcrete'], ['dependency' => null]));
        self::assertNull($invoke->invoke($app, [$target, 'acceptInterface']));

        $dependency = new NullableDependency();
        self::assertSame($dependency, $invoke->invoke($app, [$target, 'acceptConcrete'], ['dependency' => $dependency]));
    }

    private function application(string $suffix): Application
    {
        return Application::getInstance('NullableDependencies_' . $suffix . '_' . uniqid());
    }
}

interface NullableDependencyContract
{
}

class NullableDependency implements NullableDependencyContract
{
}

class NullableConcreteConsumer
{
    public function __construct(public ?NullableDependency $dependency = null)
    {
    }
}

class NullableInterfaceConsumer
{
    public function __construct(public ?NullableDependencyContract $dependency = null)
    {
    }
}

class RequiredConcreteConsumer
{
    public function __construct(public NullableDependency $dependency)
    {
    }
}

class RequiredNullableConsumer
{
    public function __construct(public ?NullableDependency $dependency)
    {
    }
}

class RequiredInterfaceConsumer
{
    public function __construct(public NullableDependencyContract $dependency)
    {
    }
}

class NullableMethodTarget
{
    public function acceptConcrete(?NullableDependency $dependency = null): ?NullableDependency
    {
        return $dependency;
    }

    public function acceptInterface(?NullableDependencyContract $dependency = null): ?NullableDependencyContract
    {
        return $dependency;
    }
}

class ScalarDefaultConsumer
{
    public function __construct(public string $name = 'default')
    {
    }
}
