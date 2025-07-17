<?php

namespace BlueFission\Parsing\Contracts;

interface IToolFunction {
    public function name(): string;
    public function execute(array $args): mixed;
}
