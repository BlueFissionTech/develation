<?php

namespace BlueFission\Parsing;

use BlueFission\Behavioral\Behaviors\State;

/**
 * Orchestrates loading input and initializing the parsing process
 */
class Parser {
    public Root $root;

    /**
     * Create a new parser instance
     */
    public function __construct(string $input, string $open = '{', string $close = '}')
    {
        $this->root = new Root($input, $open, $close);
    }

    public function setVariable($name, $value = null): void
    {
        $this->root->setScopeVariable($name, $value);
    }

    public function setVariables(array $vars): void
    {
        foreach ($vars as $name => $value) {
            $this->setVariable($name, $value);
        }
    }

    public function setIncludePaths(array $paths): void
    {
        $this->root->setIncludePaths($paths);
    }

    public function setTemplate(string $input): void
    {
        $this->root->setTemplate($input);
    }

    /**
     * Render the final output
     */
    public function render(): string
    {
        return $this->root->render();
    }

    public function root(): Root
    {
        return $this->root;
    }
}
