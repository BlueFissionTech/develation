<?php

namespace BlueFission\Parsing;

use BlueFission\Obj;
use BlueFission\Str;
use BlueFission\Collections\Collection;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\Dispatches;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Parsing\Registry\TagRegistry;
use BlueFission\Parsing\Registry\DatatypeRegistry;
use BlueFission\DevElation as Dev;

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
    protected bool $closed = false;
    protected $template;
    protected array $macros = [];
    protected array $attributes = [];
    protected array $includePaths = [];
    protected Block $block;
    protected $uuid;
    protected ?Element $parent = null;

    protected string $description = 'Generic element';

    public function __construct(string $tag, string $match, string $raw, array $attributes = [])
    {
        parent::__construct();
        $this->__dispatchConstruct();
        $this->tag = $tag;
        $this->match = $match;
        $this->raw = Dev::apply('_in', $raw);
        $this->attributes = Dev::apply('_attributes', $attributes);

        if (!$this->uuid) {
            $this->uuid = uniqid($this->getTag()."_", true);
        }

        // Set the root block this element represents
        $this->block = new Block($this->raw, $this->closed);
        $this->echo($this->block, [Event::ITEM_ADDED]);
        $this->block->setOwner($this);
    }

    public function getUuid(): string
    {
        return $this->uuid;
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
        $this->includePaths = Dev::apply('_in', $paths);
    }

    public function getIncludePaths(): array
    {
        return $this->includePaths;
    }

    public function render(): string
    {
        Dev::do('_before', [$this]);
        $this->parse();
        $this->block->process();

        if ($this->getTemplate()) {
            $templateContent = $this->getTemplate()->render();
            $this->setContent($templateContent);
        }

        $content = Dev::apply('_out', $this->block->content);
        $this->block->content = $content;
        Dev::do('_after', [$content, $this]);
        return $content;
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

    public function getTemplate(): ?Element
    {
        return $this->template;
    }

    public function setTemplate($template): void
    {
        $this->template = $template;
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

        return Dev::apply('_out', $content);
    }

    public function setContent($content): void
    {
        $this->block->content = Dev::apply('_in', $content);
    }

    public function getName(): string
    {
        $name = $this->getAttribute('name');
        $tag = $this->getTag();
        if ($name) {
            $tag = "{$tag}_".Str::slug($name);
        }

        $name = "{$tag}_".Str::slug(substr($this->getUuid(), -6));

        return $name;
    }

    public function getDescription(): string
    {
        return $this->description;
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

        $value = $this->resolveValue($value);
        return Dev::apply('_attribute', $value);
    }

    public function getAttributes(): array
    {
        $attributes = [];
        foreach ($this->attributes as $key => $value) {
            $attributes[$key] = $this->resolveValue($value);
        }

        return Dev::apply('_attributes', $attributes);
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

    public function resolveValue(string $value, ?string $type = null): mixed
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

        return Dev::apply('_value', $parsed);
    }

    public function resolveCastClass(string $cast): string
    {
        return Dev::apply('_cast', DatatypeRegistry::get($cast));
    }
}
