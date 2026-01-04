<?php

namespace BlueFission\Parsing\Contracts;

use BlueFission\Parsing\Element;

interface IValidator {
    public function validate(Element $element): bool;
}
