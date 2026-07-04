<?php
namespace BlueFission\Tests;

use BlueFission\Arr;
use BlueFission\Collections\Collection;
use BlueFission\Data\Directory;
use BlueFission\Data\File;
use BlueFission\Data\FileSystem;
use BlueFission\Date;
use BlueFission\Flag;
use BlueFission\Func;
use BlueFission\Num;
use BlueFission\Obj;
use BlueFission\Str;
use BlueFission\Val;

class FunctionsTest extends \PHPUnit\Framework\TestCase
{
    public function testPrimitiveHelpersReturnValueObjects()
    {
        $this->assertInstanceOf(Val::class, val('alpha'));
        $this->assertSame('alpha', val('alpha')->val());

        $this->assertInstanceOf(Str::class, str('alpha'));
        $this->assertSame('ALPHA', str('alpha')->upper()->val());

        $this->assertInstanceOf(Arr::class, arr(['one']));
        $this->assertSame(['one'], arr(['one'])->val());

        $this->assertInstanceOf(Num::class, num(2));
        $this->assertSame(5, num(2)->plus(3)->val());

        $this->assertInstanceOf(Flag::class, flag('yes'));
        $this->assertTrue(flag('yes')->parseBool());

        $callable = fn () => 'done';
        $this->assertInstanceOf(Func::class, func($callable));
        $this->assertSame('done', func($callable)->call());

        $this->assertInstanceOf(Date::class, datetime('2026-07-04'));
    }

    public function testObjectAndCollectionHelpers()
    {
        $object = obj();
        $this->assertInstanceOf(Obj::class, $object);
        $object->assign(['name' => 'Ada']);
        $this->assertSame('Ada', $object->field('name'));

        $collection = collect(['first' => 'alpha']);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame('alpha', $collection->get('first'));
    }

    public function testFilesystemHelpers()
    {
        $filesystem = filesystem(['root' => __DIR__]);
        $this->assertInstanceOf(FileSystem::class, $filesystem);

        $file = bf_file();
        $this->assertInstanceOf(File::class, $file);
        $this->assertTrue($file->exists(__FILE__));
        $this->assertSame($file, $file->contents('contents'));
        $this->assertSame('contents', $file->contents());

        $directory = directory();
        $this->assertInstanceOf(Directory::class, $directory);
        $this->assertTrue($directory->exists(__DIR__));
    }

    public function testGlobalHelpersAvoidPhpBuiltInCollisions()
    {
        $this->assertTrue(function_exists('file'));
        $this->assertTrue(function_exists('bf_file'));
        $this->assertNotSame('bf_file', 'file');

        $this->assertTrue(function_exists('date'));
        $this->assertTrue(function_exists('datetime'));
        $this->assertFalse(function_exists('bf_date'));
    }
}
