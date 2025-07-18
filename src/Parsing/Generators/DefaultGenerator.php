<?php

namespace BlueFission\Parsing\Generators;

use BlueFission\Parsing\Contracts\IGenerator;
use BlueFission\Parsing\Element;

class DefaultGenerator implements IGenerator {
    public function generate(Element $element): string {
        $prompt = $this->getAttribute('prompt') ?? 'Enter text: ';
        $default = $this->getAttribute('default') ?? 'Lorem Ipsum';
        $input = $default;

        // If CLI 
        if (php_sapi_name() === 'cli') {
            $input = readline("Enter text: ");
            return trim($input);
        } else {
            // Ideally, we can fire an event to trigger a prompt on the front end. If not, 
            // we'll suspect a form field

            // For web, we can use a simple HTML input
            $input = '<input type="text" name="' . $element->getName() . '" value="' . htmlspecialchars($default) . '" placeholder="' . htmlspecialchars($prompt) . '">';
            return $input;
        }

        return $input;
    }
}
