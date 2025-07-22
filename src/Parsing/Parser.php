<?php

namespace BlueFission\Parsing;

use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\Dispatches;

/**
 * Orchestrates loading input and initializing the parsing process
 */
class Parser extends Dispatches {
    use Dispatches {
        Dispatches::__construct as private __dispatchConstruct;
    }

    public Root $root;

    /**
     * Create a new parser instance
     */
    public function __construct(string $input, string $open = '{', string $close = '}')
    {
        $this->__dispatchConstruct();
        $this->root = new Root($input, $open, $close);
        $this->echo($this->root, [Event::STARTED, Event::SENT, Event::RECEIVED, Event::COMPLETE]);
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
