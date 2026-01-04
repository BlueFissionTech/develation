<?php

namespace BlueFission\Parsing\Contracts;

use BlueFission\Parsing\Element;

interface IProcessor {
    public function process($content): string;
}
