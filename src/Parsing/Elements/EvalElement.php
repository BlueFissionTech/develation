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
use BlueFission\Flag;
use BlueFission\Str;
use BlueFission\Val;
use BlueFission\DevElation as Dev;

class EvalElement extends Element implements IExecutableElement, IRenderableElement
{
    protected string $name;
    protected array $params;
    protected string $var;
    protected string $type;
    protected mixed $value = null;
    protected $generatorDriver;
    protected bool $executed = false;

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
        if (Str::pos($this->name, ':')) {
            $parts = explode(':', $this->name, 2);
            $this->name = $parts[0];
            $this->type = $parts[1];
        } else {
            $this->type = 'val'; // Default type
        }

        $this->value = $this->evaluator->evaluate($this->evaluationExpression());
        $this->executed = true;
        $this->value = Dev::apply('_out', $this->value);
        Dev::do('_after', [$this->value, $this]);

        return $this->value;
    }

    public function render(): string
    {
        Dev::do('_before', [$this]);

        if (!$this->executed) {
            $this->execute();
        }

        if ($this->shouldSuppressOutput()) {
            Dev::do('_after', ['', $this]);
            return '';
        }

        $output = $this->expressionOutput();
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);

        return $output;
    }

    public function getDescription(): string
    {
        $descriptionString = sprintf('Evalute the expression "%s" and return the result.', $this->name);

        $this->description = $descriptionString;

        return $this->description;
    }

    protected function shouldSuppressOutput(): bool
    {
        $silent = $this->attributes['silent'] ?? null;
        if (Flag::parseBool($silent, false)) {
            return true;
        }

        return (bool)preg_match(
            '/->\s*\$?[a-zA-Z_][a-zA-Z0-9_-]*(?:\[\])?(?:&)?(?::[a-zA-Z_][a-zA-Z0-9_-]*)?\s*$/',
            $this->evaluationExpression()
        );
    }

    protected function expressionOutput(): string
    {
        $expression = (string)($this->attributes['expression'] ?? '');
        $params = (string)($this->attributes['params'] ?? '');
        $invoked = Flag::parseBool($this->attributes['invoked'] ?? false, false);

        if ($invoked) {
            return "{$expression}({$params})";
        }

        return $expression;
    }

    protected function evaluationExpression(): string
    {
        $expression = $this->expressionOutput();
        $match = $this->getMatch();

        if (preg_match(
            '/->\s*(\$?[a-zA-Z_][a-zA-Z0-9_-]*(?:\[\])?(?:&)?(?::[a-zA-Z_][a-zA-Z0-9_-]*)?)/',
            $match,
            $assignMatch
        )) {
            $expression .= ' -> ' . $assignMatch[1];
        }

        return $expression;
    }
}
