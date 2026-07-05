<?php

namespace BlueFission\Tests;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\DevElation as Dev;
use BlueFission\Ref;

class RefTest extends ValTest
{
    public static $classname = '\BlueFission\Ref';

    public function tearDown(): void
    {
        Dev::down();
    }

    public function testBindPreservesReferenceMutation()
    {
        $value = 'first';
        $ref = Ref::bind($value);

        $ref->val('second');

        $this->assertSame('second', $value);
        $this->assertSame('second', $ref->unwrap());
        $this->assertTrue($ref->valid());
    }

    public function testResourceWrapsCallerOwnedHandleWithoutClosingIt()
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, 'caller');
        rewind($handle);

        $ref = Ref::resource($handle);

        $this->assertSame('caller', $ref->read());
        $ref->close();

        $this->assertTrue(is_resource($handle));
        fclose($handle);
    }

    public function testOwnedResourceClosesDeterministically()
    {
        $handle = fopen('php://temp', 'r+');
        $ref = Ref::resource($handle, ['owned' => true]);

        $ref->close();

        $this->assertFalse(is_resource($handle));
        $this->assertFalse($ref->valid());
        $this->assertSame('closed', $ref->meta('status'));
    }

    public function testReadWriteDispatchLifecycleEvents()
    {
        $handle = fopen('php://temp', 'r+');
        $ref = Ref::resource($handle, ['owned' => true]);
        $events = [];

        $ref->behavior(Event::SENT, function () use (&$events) {
            $events[] = Event::SENT;
        });
        $ref->behavior(Event::READ, function () use (&$events) {
            $events[] = Event::READ;
        });
        $ref->behavior(Event::DISCONNECTED, function () use (&$events) {
            $events[] = Event::DISCONNECTED;
        });

        $this->assertSame(4, $ref->write('data'));
        rewind($handle);
        $this->assertSame('data', $ref->read());
        $ref->close();

        $this->assertContains(Event::SENT, $events);
        $this->assertContains(Event::READ, $events);
        $this->assertContains(Event::DISCONNECTED, $events);
    }

    public function testDevElationFiltersWrapReadAndWriteBoundaries()
    {
        Dev::up();
        Dev::filter('_in', fn ($value) => is_string($value) ? strtoupper($value) : $value);
        Dev::filter('_out', fn ($value) => is_string($value) ? strtolower($value) : $value);

        $handle = fopen('php://temp', 'r+');
        $ref = Ref::resource($handle, ['owned' => true]);

        $ref->write('mixed');
        rewind($handle);

        $this->assertSame('mixed', $ref->read());
        rewind($handle);
        $this->assertSame('MIXED', stream_get_contents($handle));
    }

    public function testStaticValidHelperMirrorsValFamilyInterface()
    {
        $handle = fopen('php://temp', 'r+');

        $this->assertTrue(Ref::valid($handle));

        fclose($handle);
    }

    public function testGlobalHelperCreatesRef()
    {
        $this->assertInstanceOf(Ref::class, ref('value'));
    }
}
