<?php

namespace BlueFission\Async;

interface IAsync {
    public static function exec($function, $args = []);
    public static function run();
}