<?php

namespace BlueFission\Parsing;

use BlueFission\Obj;

/**
 * Represents a matched element in the template
 */
class Element extends Obj {
    protected string $tag;
    protected string $raw;
    protected string $match;
    protected $template;
    protected array $sections = [];
    protected array $attributes = [];
    protected array $includePaths = [];
    protected Block $block;
    protected ?Element $parent = null;

    public function __construct(string $tag, string $match, string $raw, array $attributes = [])
    {
        parent::__construct();
        $this->tag = $tag;
        $this->match = $match;
        $this->raw = $raw;
        $this->attributes = $attributes;

        // Set the root block this element represents
        $this->block = new Block($this->raw);
        $this->block->setOwner($this);
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

    public function setScopeVariable(string $name, mixed $value): void
    {
        $this->block->setVar($name, $value);
    }

    public function getScopeVariable(string $name): mixed
    {
        return $this->block->getVar($name);
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

    public function getRaw(): string
    {
        return $this->raw;
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

    protected function resolveValue(string $value, ?string $type = null): mixed
    {
        $firstChar = substr($value, 0, 1);
        $lastChar = substr($value, -1);

        $parsed = match (true) {
            $firstChar === '"' || $firstChar === "'" => trim($value, "'\""),
            $firstChar === '[' => json_decode(str_replace("'", '"', $value), true),
            $firstChar === '{' => json_decode($value, true),
            preg_match('/^[a-zA-Z_]/', $value) => $this->getScopeVariable($value),
            default => (float)$value,
        };

        if ($type === 'json') {
            $parsed = json_decode($value, true);
        }

        return $parsed;
    }
}