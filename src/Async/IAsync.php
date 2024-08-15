<?php

namespace BlueFission\Async;

interface IAsync {
    public static function exec($_function, $_args = []);
    public static function run();
}