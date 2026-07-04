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
    function obj(): Obj
    {
        return new Obj();
    }
}

if (!function_exists('collect')) {
    function collect(mixed $value = null): Collection
    {
        return new Collection($value);
    }
}

if (!function_exists('datetime')) {
    function datetime(mixed $value = null): Date
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
    function bf_file(): File
    {
        return new File();
    }
}

if (!function_exists('directory')) {
    function directory(?IData $storage = null): Directory
    {
        return new class($storage ?? new FileSystem()) extends Directory {};
    }
}
