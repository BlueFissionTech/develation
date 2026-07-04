<?php

use BlueFission\Arr;
use BlueFission\Date;
use BlueFission\Flag;
use BlueFission\Func;
use BlueFission\Num;
use BlueFission\Obj;
use BlueFission\Str;
use BlueFission\Val;
use BlueFission\Collections\Collection;
use BlueFission\Data\Directory;
use BlueFission\Data\File;
use BlueFission\Data\FileSystem;
use BlueFission\Data\IData;

if (!function_exists('val')) {
    function val(mixed $value = null): Val
    {
        return Val::make($value);
    }
}

if (!function_exists('str')) {
    function str(mixed $value = null): Str
    {
        return Str::make($value);
    }
}

if (!function_exists('arr')) {
    function arr(mixed $value = null): Arr
    {
        return Arr::make($value);
    }
}

if (!function_exists('num')) {
    function num(mixed $value = null): Num
    {
        return Num::make($value);
    }
}

if (!function_exists('flag')) {
    function flag(mixed $value = null): Flag
    {
        return Flag::make($value);
    }
}

if (!function_exists('func')) {
    function func(mixed $value = null): Func
    {
        return Func::make($value);
    }
}

if (!function_exists('obj')) {
    function obj(array|object|null $data = null): Obj
    {
        $object = new Obj();

        if ($data !== null) {
            $object->assign($data);
        }

        return $object;
    }
}

if (!function_exists('collect')) {
    function collect(mixed $value = null): Collection
    {
        return new Collection($value);
    }
}

if (!function_exists('bf_date')) {
    function bf_date(mixed $value = null): Date
    {
        return Date::make($value);
    }
}

if (!function_exists('filesystem')) {
    function filesystem(mixed $config = null): FileSystem
    {
        return new FileSystem($config);
    }
}

if (!function_exists('bf_file')) {
    function bf_file(?string $path = null, mixed $contents = null): File
    {
        $file = new File();

        if ($path !== null) {
            $file->label($path);
        }

        if ($contents !== null) {
            $file->contents($contents);
        }

        return $file;
    }
}

if (!function_exists('directory')) {
    function directory(mixed $pathOrStorage = null): Directory
    {
        $storage = $pathOrStorage instanceof IData
            ? $pathOrStorage
            : new FileSystem(Arr::is($pathOrStorage) ? $pathOrStorage : ['root' => (string)($pathOrStorage ?? ''), 'filter' => []]);

        $directory = new class($storage) extends Directory {};

        if (Str::is($pathOrStorage) && $pathOrStorage !== '') {
            $directory->label($pathOrStorage);
        }

        return $directory;
    }
}
