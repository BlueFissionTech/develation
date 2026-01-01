<?php

namespace BlueFission\Parsing\Generators;

use BlueFission\Parsing\Contracts\IGenerator;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class DefaultGenerator implements IGenerator {
    public function generate(Element $element): string {
        Dev::do('_before', [$element]);
        $prompt = $element->getAttribute('prompt') ?? 'Enter text: ';
        $default = $element->getAttribute('default') ?? 'Lorem Ipsum';
        $input = $default;

        // If CLI 
        if (php_sapi_name() === 'cli') {
            $input = readline("Enter text: ");
            $input = trim($input);
            $input = Dev::apply('_out', $input);
            Dev::do('_after', [$input, $element]);
            return $input;
        } else {
            // Ideally, we can fire an event to trigger a prompt on the front end. If not, 
            // we'll suspect a form field

            // For web, we can use a simple HTML input
            $input = '<input type="text" name="' . $element->getName() . '" value="' . htmlspecialchars($default) . '" placeholder="' . htmlspecialchars($prompt) . '">';
            $input = Dev::apply('_out', $input);
            Dev::do('_after', [$input, $element]);
            return $input;
        }

        $input = Dev::apply('_out', $input);
        Dev::do('_after', [$input, $element]);
        return $input;
    }
}
