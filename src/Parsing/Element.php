<?php

namespace BlueFission\Parsing;

use BlueFission\Obj;
use BlueFission\Str;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\Dispatches;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Parsing\Registry\TagRegistry;

/**
 * Represents a matched element in the template
 */
class Element extends Obj {
    use Dispatches {
        Dispatches::__construct as private __dispatchConstruct;
    }

    protected string $tag;
    protected string $raw;
    protected string $match;
    protected $template;
    protected array $sections = [];
    protected array $macros = [];
    protected array $attributes = [];
    protected array $includePaths = [];
    protected Block $block;
    protected $uuid;
    protected ?Element $parent = null;

    public function __construct(string $tag, string $match, string $raw, array $attributes = [])
    {
        parent::__construct();
        $this->__dispatchConstruct();
        $this->tag = $tag;
        $this->match = $match;
        $this->raw = $raw;
        $this->attributes = $attributes;

        if (!$this->uuid) {
            $this->uuid = uniqid($this->getTag()."_", true);
        }

        // Set the root block this element represents
        $this->block = new Block($this->raw);
        $this->block->setOwner($this);
        $this->echo($this->block, [Event::STARTED, Event::SENT, Event::ERROR, Event::RECEIVED, Event::COMPLETE]);
    }

    public function parse(): void
    {
        if ( $this->block->active ) {
            $this->block->parse();
        }
    }

    public function isClosed(): bool
    {
        return $this->block->isClosed();
    }

    public function hasScopeVariable(string $name): bool
    {
        return $this->block->hasVar($name);
    }

    public function setScopeVariable(string $name, mixed $value): void
    {
        $this->block->setVar($name, $value);
    }

    public function getScopeVariable(string $name): mixed
    {
        return $this->block->getVar($name);
    }

    public function getAllVariables(): array
    {
        return $this->block->allVars();
    }

    public function setIncludePaths(array $paths): void {
        $this->includePaths = $paths;
    }

    public function getIncludePaths(): array
    {
        return $this->includePaths;
    }

    public function render(): string
    {
        $this->parse();
        $this->block->process();

        if ($this->template) {
            foreach ($this->sections as $name => $section) {
                $this->template->addOutput($name, $section->build());
            }

            return $this->template->build();
        }

        return $this->block->content;
    }

    public function getMatch(): string
    {
        return $this->match;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getParent(): ?Element
    {
        return $this->parent;
    }

    public function setParent(Element $parent): void
    {
        $this->parent = $parent;
    }

    public function setTemplate(Element $template): void
    {
        $this->template = $template;
    }

    public function addSection(string $name, Element $section): void
    {
        $this->sections[$name] = $section;
    }

    public function addMacro(string $name, Element $macro): void
    {
       $this->macro[$name] = $macro;
    }

    public function getRaw(): string
    {
        return $this->raw;
    }

    public function getContent(): string
    {
        $content = $this->block->content;

        return $content;
    }

    public function children(): array
    {
        return $this->block->allElements();
    }

    public function getAttribute($name): mixed
    {
        if (!isset($this->attributes[$name])) {
            return null;
        }

        $value = $this->attributes[$name];

        return $this->resolveValue($value);
    }

    public function getAttributes(): array
    {
        $attributes = [];
        foreach ($this->attributes as $key => $value) {
            $attributes[$key] = $this->resolveValue($value);
        }

        return $attributes;
    }

    public function getRoot(): Element
    {
        $current = $this;
        while ($current && $current->getParent()) {
            $current = $current->getParent();
            if ($current->getTag() === TagRegistry::ROOT) {
                return $current;
            }
        }

        return $current ?: new Element(TagRegistry::ROOT, '', []);
    }

    public function getTop(): Element
    {
        $current = $this;
        while ($current && $current->getParent()) {
            $current = $current->getParent();
            if ($current->getTag() === TagRegistry::ROOT || $current->isClosed()) {
                return $current;
            }
        }

        return $current ?: new Element(TagRegistry::ROOT, '', []);
    }

    protected function getNestedValue($dotNotationString, $varName = null): mixed
    {
        $parts = explode('.', $dotNotationString);
        $name = array_shift($parts);
        $varName = $varName ?? $name;
        $value = $this->block->getVar($varName);
        foreach ($parts as $part) {
            if (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } elseif (is_object($value) && property_exists($value, $part)) {
                $value = $value->$part;
            } else {
                return null; // Return null if the path does not exist
            }
        }

        return $value;
    }

    protected function resolveValue(string $value, ?string $type = null): mixed
    {
        $firstChar = substr($value, 0, 1);
        $lastChar = substr($value, -1);

        $parsed = match (true) {
            $firstChar === '"' || $firstChar === "'" => trim($value, "'\""),
            $firstChar === '[' => json_decode(str_replace("'", '"', $value), true),
            $firstChar === '{' => json_decode($value, true),
            (bool)preg_match('/(^[a-zA-Z_]+)/', $value) => $this->getScopeVariable($value),
            default => (float)$value,
        };

        if ($type === 'json') {
            $parsed = json_decode($parsed, true);
        }

        return $parsed;
    }

    protected function resolveCastClass(string $cast): string
    {
        $map = [
            'text' => \BlueFission\Str::class,
            'number' => \BlueFission\Num::class,
            'flag' => \BlueFission\Flag::class,
            'value' => \BlueFission\Val::class,
            'val' => \BlueFission\Val::class,
            'list' => \BlueFission\Arr::class,
            'date' => \BlueFission\Date::class,
            'object' => \BlueFission\Obj::class,
            'macro' => \BlueFission\Func::class,
        ];

        return $map[strtolower($cast)] ?? \BlueFission\Val::class;
    }

}