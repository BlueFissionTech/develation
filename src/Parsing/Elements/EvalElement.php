<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Block;
use BlueFission\Parsing\Evaluator;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Parsing\Registry\GeneratorRegistry;
use BlueFission\Parsing\Registry\FunctionRegistry;
use BlueFission\Parsing\Registry\StandardRegistry;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Contracts\IExecutableElement;
use BlueFission\Val;
use BlueFission\DevElation as Dev;

class EvalElement extends Element implements IExecutableElement, IRenderableElement
{
    protected string $name;
    protected array $params;
    protected string $var;
    protected string $type;
    protected string $value = '';
    protected $generatorDriver;

    public function __construct(string $tag, string $match, string $raw, array $attributes = [])
    {
        parent::__construct($tag, $match, $raw, $attributes);
        $this->evaluator = new Evaluator($this);
    }

    public function setDriver($driver): void
    {
        $this->evaluator->setDriver($driver);
    }

    public function execute(): mixed
    {
        // Evaluation here is side-effect driven; extensions control output behavior.
        Dev::do('_before', [$this]);
        $this->name = $this->attributes['expression'];
        if (strpos($this->name, ':')) {
            $parts = explode(':', $this->name, 2);
            $this->name = $parts[0];
            $this->type = $parts[1];
        } else {
            $this->type = 'val'; // Default type
        }

        $this->value = $this->evaluator->evaluate($this->raw);
        $this->value = Dev::apply('_out', $this->value);
        Dev::do('_after', [$this->value, $this]);

        return $this->value;
    }

    public function getDescription(): string
    {
        $descriptionString = sprintf('Evalute the expression "%s" and return the result.', $this->name);

        $this->description = $descriptionString;

        return $this->description;
    }
}
