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

    public function testIsRecognizesReferencePrimitiveValues()
    {
        $handle = fopen('php://temp', 'r+');

        $this->assertTrue(Ref::is($handle));
        $this->assertFalse(Ref::is('php://temp'));
        $this->assertFalse(Ref::is(null));

        fclose($handle);
    }

    public function testOpenCreatesOwnedStreamFromTarget()
    {
        $path = tempnam(sys_get_temp_dir(), 'ref-open-');
        file_put_contents($path, 'opened');

        $ref = Ref::open($path);

        $this->assertTrue($ref->valid());
        $this->assertTrue($ref->isOwned());
        $this->assertSame('opened', $ref->read());
        $ref->close();

        unlink($path);
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

    public function testCursorHelpersMoveAndReportStreamPosition()
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, 'abcdef');
        rewind($handle);

        $ref = Ref::resource($handle, ['owned' => true]);

        $this->assertSame(0, $ref->tell());
        $this->assertSame('ab', $ref->read(2));
        $this->assertSame(2, $ref->tell());
        $this->assertSame('de', $ref->seek(3)->read(2));
        $this->assertFalse($ref->eof());
        $this->assertSame('abcdef', $ref->rewind()->read());
        $this->assertTrue($ref->eof());
    }

    public function testTruncateShortensOwnedStream()
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, 'abcdef');
        rewind($handle);

        $ref = Ref::resource($handle, ['owned' => true]);

        $this->assertTrue($ref->truncate(3));
        $this->assertSame('abc', $ref->rewind()->read());
    }

    public function testChunksIterateReadableStreamWithoutLoadingAllData()
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, 'abcdef');
        rewind($handle);

        $ref = Ref::resource($handle, ['owned' => true]);

        $this->assertSame(['ab', 'cd', 'ef'], iterator_to_array($ref->chunks(2)));
        $this->assertTrue($ref->eof());
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

    public function testsGrab()
    {
        $handle = fopen('php://temp', 'r+');

        if (Ref::is($handle)) {
            $this->assertSame($handle, Ref::grab());
        }

        fclose($handle);
    }

    public function testsUse()
    {
        $handle = fopen('php://temp', 'r+');

        if (Ref::is($handle)) {
            $ref = Ref::use();
            $this->assertInstanceOf(Ref::class, $ref);
            $this->assertSame($handle, $ref->val());
        }

        fclose($handle);
    }

    public function testGlobalHelperCreatesRef()
    {
        $this->assertInstanceOf(Ref::class, ref('value'));
    }
}
