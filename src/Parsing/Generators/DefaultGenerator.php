<?php

namespace BlueFission\Parsing\Generators;

use BlueFission\Parsing\Contracts\IGenerator;
use BlueFission\Parsing\Element;

class DefaultGenerator implements IGenerator {
    public function generate(Element $element): string {
        return "Lorem Ipsum";
    }
}
