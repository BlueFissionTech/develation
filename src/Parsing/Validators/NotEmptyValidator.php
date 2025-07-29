<?php

namespace BlueFission\Parsing\Validators;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IValidator;

class NotEmptyValidator implements IValidator {
    public function validate(Element $element): bool
    {
        $content = $element->getContent();
        return !empty($content) && trim($content) !== '';
    }
}