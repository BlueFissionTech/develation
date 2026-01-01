<?php

namespace BlueFission\Parsing;

use BlueFission\Obj;
use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\Val;
use BlueFission\Flag;
use BlueFission\DataTypes;
use BlueFission\Behavioral\Dispatches;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Parsing\Registry\RendererRegistry;
use BlueFission\Parsing\Registry\ExecutorRegistry;
use BlueFission\Parsing\Registry\TagRegistry;
use BlueFission\Parsing\Registry\PreparerRegistry;
use BlueFission\Parsing\Contracts\ILoopElement;
use BlueFission\Parsing\Contracts\IConditionElement;
use BlueFission\Parsing\Contracts\IExecutableElement;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\DevElation as Dev;

/**
 * Represents a logical block of content, used for root or element scope
 */
class Block extends Obj {
    use Dispatches {
        Dispatches::__construct as private __dispatchConstruct;
    }

    protected $_types = [
        'vars' => DataTypes::ARRAY,
        'index' => DataTypes::ARRAY,
    ];

    protected $_data = [
        'vars' => [],
        'index' => [],
    ];

    protected $_lockDataType = true;

    protected $_exposeValueObject = true;
    
    public Block $block;
    public string $content = '';
    public bool $closed = false;
    public bool $active = true;
    public string $open = '{';
    public string $close = '}';
    public array $elements = [];
    public array $refs = [];

    protected ?Element $owner = null;

    private Element $root;

    public function __construct(string $content = '', $closed = false, $open = null, $close = null)
    {
        parent::__construct();
        $this->__dispatchConstruct();
        $this->content = $content;
        $this->closed = $closed;

        $this->open = $open ?: $this->open;
        $this->close = $close ?: $this->close;
    }

    public function parse(): void
    {
        $this->content = Dev::apply('_in', $this->content);
        Dev::do('_before', [$this->content, $this]);
        $pattern = TagRegistry::unifiedPattern();

        if (preg_match_all($pattern, $this->content, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                foreach (TagRegistry::all() as $definition) {
                    $tag = $definition->name;

                    if (isset($match[$tag]) && !empty($match[$tag][0])) {
                        $capture = $match[$tag][0];
                        $raw = end($match)[0];
                        $attributes = TagRegistry::extractAttributes($tag, $match[$tag][0]);
                        $attributes = Dev::apply('_attributes', $attributes);
                        $elementClass = TagRegistry::get($tag)->class;
                        $element = new $elementClass($tag, $capture, $raw, $attributes);
                        $element = Dev::apply('_element', $element);
                        $this->prepareElement($element);
                        $this->elements[] = $element;
                        $this->perform(Event::ITEM_ADDED, new Meta(
                            src: $this,
                            data: $element,
                        ));
                        break;
                    }
                }
            }
        }
        Dev::do('_after', [$this->elements, $this]);
    }

    public function process(): void
    {
        $this->perform(State::PROCESSING);
        Dev::do('_before', [$this]);
        foreach ($this->elements as $element) {
            $output = '';
            $result = null;
            $renderer = RendererRegistry::get($element->getTag());
            $executor = ExecutorRegistry::get($element->getTag());
            Dev::do('parsing.block.before_element', [$element, $this]);

            if ($element instanceof IConditionElement) {
                if ($element->evaluate()) {
                    $output .= $renderer->render($element);
                }
            } elseif ($element instanceof ILoopElement) {
                $output = $element->run($this->allVars());
            } elseif ($element instanceof IExecutableElement) {
                $result = $executor->execute($element);
                $output = $renderer->render($element);
            } elseif ($element instanceof IRenderableElement) {
                $output = $renderer->render($element);
            }

            $output = Dev::apply('_output', $output);
            $this->content = Str::replace($this->content, $element->getMatch(), $output);
            Dev::do('parsing.block.after_element', [$element, $output, $this]);
        }
        $this->perform(Event::PROCESSED);
        $this->halt(State::PROCESSING);
        Dev::do('_after', [$this->content, $this]);
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function setOwner(Element $owner): void
    {
        $this->owner = $owner;
    }

    public function getOwner(): ?Element
    {
        return $this->owner;
    }

    public function setVar(string $name, mixed $value): void
    {
        $this->vars[$name] = $value;//Val::make($value);
        if ( !$this->closed && 
            $this->owner &&
            $this->owner?->getParent()?->getScopeVariable($name) !== $value ) {
            $this->owner?->getParent()?->setScopeVariable($name, $value);
        }

        foreach ($this->elements as $element) {
            if (!$element->isClosed() &&
                $element->getScopeVariable($name) !== $value ) {
                $element->setScopeVariable($name, $value);
            }
        }
    }

    public function getVar(string $name): mixed
    {
        $vars = $this->field('vars');
        return $vars[$name] ?? null;
    }

    public function hasVar(string $name): bool
    {
        return $this->vars->hasKey($name);
    }

    public function unsetVar(string $name): void
    {
        $vars = $this->field('vars');
        unset($vars[$name]);
        $this->field('vars', $vars);
    }

    public function setContent(string $content): void
    {
        $this->content = Dev::apply('_in', $content);
    }

    public function allVars(): array
    {
        return $this->field('vars')->val() ?? [];
    }

    public function getSection(string $name): ?Element
    {
        $sections = $this->field('sections');
        return $sections[$name] ?? null;
    }

    public function allElements(): array
    {
        return $this->elements;
    }

    protected function prepareElement($element): void
    {
        foreach (PreparerRegistry::all() as $preparer) {
            if ($preparer->supports($element)) {
                $preparer->setContext($this->owner);
                $preparer->prepare($element);
            }
        }
    }
}
