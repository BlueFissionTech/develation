<?php

namespace BlueFission\Parsing;

use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\Dispatches;
use BlueFission\DevElation as Dev;

/**
 * Orchestrates loading input and initializing the parsing process
 */
class Parser implements IDispatcher {
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
        $input = Dev::apply('_in', $input);
        Dev::do('_before', [$input, $open, $close]);
        $this->root = new Root($input, $open, $close);
        $this->echo($this->root, [Event::SENT, Event::RECEIVED, Event::ERROR, Event::ITEM_ADDED, State::RUNNING, State::IDLE]);
        Dev::do('_after', [$this->root]);
    }

    public function setVariable($name, $value = null): void
    {
        $value = Dev::apply('_in', $value);
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
        $paths = Dev::apply('_in', $paths);
        $this->root->setIncludePaths($paths);
    }

    public function setTemplate(string $input): void
    {
        $input = Dev::apply('_in', $input);
        $this->root->setTemplate($input);
    }

    /**
     * Render the final output
     */
    public function render(): string
    {
        Dev::do('_before', [$this]);
        $output = $this->root->render();
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function root(): Root
    {
        return $this->root;
    }
}
