<?php

namespace BlueFission\Tests;

use BlueFission\Arr;
use BlueFission\Date;
use BlueFission\Flag;
use BlueFission\Func;
use BlueFission\Num;
use BlueFission\Ref;
use BlueFission\Str;
use BlueFission\Val;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class PrimitiveConventionTest extends TestCase
{
    public function testStaticMethodsDoNotShadowPseudoPrimitiveMethods(): void
    {
        $classes = [
            Val::class,
            Arr::class,
            Date::class,
            Flag::class,
            Func::class,
            Num::class,
            Ref::class,
            Str::class,
        ];
        $collisions = [];

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            $staticMethods = [];
            $pseudoMethods = [];

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $name = $method->getName();

                if ($method->isStatic()) {
                    $staticMethods[strtolower($name)] = $name;
                } elseif (str_starts_with($name, '_') && !str_starts_with($name, '__')) {
                    $pseudoMethods[strtolower(substr($name, 1))] = $name;
                }
            }

            foreach (array_intersect_key($staticMethods, $pseudoMethods) as $name => $staticMethod) {
                $collisions[] = sprintf(
                    '%s::%s() shadows %s()',
                    $class,
                    $staticMethod,
                    $pseudoMethods[$name]
                );
            }
        }

        $this->assertSame(
            [],
            $collisions,
            "Pseudo-primitive operations must use underscored instance methods and Val::__callStatic().\n"
                . implode("\n", $collisions)
        );
    }
}
