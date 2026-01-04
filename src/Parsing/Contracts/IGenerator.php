<?php

namespace BlueFission\Parsing\Contracts;

use BlueFission\Parsing\Element;

interface IGenerator {
    public function generate(Element $element): string;
}