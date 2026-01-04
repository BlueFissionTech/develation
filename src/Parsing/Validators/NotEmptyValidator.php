<?php

namespace BlueFission\Parsing\Validators;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IValidator;
use BlueFission\DevElation as Dev;

class NotEmptyValidator implements IValidator {
    public function validate(Element $element): bool
    {
        $content = $element->getContent();
        $result = !empty($content) && trim($content) !== '';
        return Dev::apply('_out', $result);
    }
}
