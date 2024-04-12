<?php

namespace BlueFission\Async;

interface IAsync {
    public function exec($function, $args = []);
    public function run();
}