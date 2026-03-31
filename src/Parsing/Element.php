<?php

namespace BlueFission\Parsing;

use BlueFission\Obj;
use BlueFission\Str;
use BlueFission\Num;
use BlueFission\Arr;
use BlueFission\Val;
use BlueFission\Flag;
use BlueFission\IVal;
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
        // Register macros on the nearest "top" element (limited scope root).
        // This respects scoped blocks where ROOT may not be the intended
        // macro namespace, but getTop() still allows @invoke to find macros
        // consistently within that scope.
        $top = $this->getTop();
        $top->macros[$name] = $macro;
    }

    public function getMacro(string $name): ?Element
    {
        $top = $this->getTop();
        return $top->macros[$name] ?? null;
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

    public function hasPathValue(string $path): bool
    {
        $segments = Str::make($path)->split('.')->val();
        $segments = array_values(array_filter($segments, fn ($segment) => $segment !== ''));

        if (empty($segments)) {
            return false;
        }

        $value = $this->block->getVar(array_shift($segments));
        if ($value === null) {
            return false;
        }

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
                continue;
            }

            if ($value instanceof Obj) {
                $data = $value->toArray();
                if (array_key_exists($segment, $data)) {
                    $value = $value->$segment;
                    continue;
                }
            }

            if (is_object($value) && (property_exists($value, $segment) || isset($value->$segment))) {
                $value = $value->$segment;
                continue;
            }

            return false;
        }

        return true;
    }

    public function getPathValue(string $path, bool $throw = false): mixed
    {
        $segments = Str::make($path)->split('.')->val();
        $segments = array_values(array_filter($segments, fn ($segment) => $segment !== ''));

        if (empty($segments)) {
            if ($throw) {
                throw new \RuntimeException("Undefined value path '{$path}'.");
            }

            return null;
        }

        $value = $this->block->getVar(array_shift($segments));

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
                continue;
            }

            if ($value instanceof Obj) {
                $data = $value->toArray();
                if (array_key_exists($segment, $data)) {
                    $value = $value->$segment;
                    continue;
                }
            }

            if (is_object($value) && (property_exists($value, $segment) || isset($value->$segment))) {
                $value = $value->$segment;
                continue;
            }

            if ($throw) {
                throw new \RuntimeException("Undefined value path '{$path}'.");
            }

            return null;
        }

        return $value;
    }

    public function setPathValue(string $path, mixed $value): void
    {
        $segments = Str::make($path)->split('.')->val();
        $segments = array_values(array_filter($segments, fn ($segment) => $segment !== ''));

        if (empty($segments)) {
            return;
        }

        $root = array_shift($segments);

        if (empty($segments)) {
            $this->setScopeVariable($root, $value);
            return;
        }

        $container = $this->getScopeVariable($root);
        if ($container === null) {
            throw new \RuntimeException("Undefined value path '{$path}'.");
        }

        $container = $this->setNestedPathValue($container, $segments, $value, $path);
        $this->setScopeVariable($root, $container);
    }

    public function parseScopedTransform(string $expression): array
    {
        $expression = Str::trim($expression);
        $result = [
            'path' => $expression,
            'chain' => '',
            'clone' => false,
            'mutate' => false,
        ];

        if (preg_match('/^(?<path>[a-zA-Z_][a-zA-Z0-9_.-]*)\s*->\s*\$(?<chain>(?:\.[a-zA-Z_][a-zA-Z0-9_-]*\([^)]*\))+)$/', $expression, $matches)) {
            $result['path'] = $matches['path'];
            $result['chain'] = $matches['chain'];
            $result['clone'] = true;

            return $result;
        }

        if (preg_match('/^(?<path>[a-zA-Z_][a-zA-Z0-9_.-]*?)(?<chain>(?:\.[a-zA-Z_][a-zA-Z0-9_-]*\([^)]*\))+)$/', $expression, $matches)) {
            $result['path'] = $matches['path'];
            $result['chain'] = $matches['chain'];
            $result['mutate'] = true;
        }

        return $result;
    }

    public function applyScopedTransform(mixed $value, string $chain): mixed
    {
        if ($chain === '') {
            return $value;
        }

        $target = $this->wrapTransformValue($value);

        preg_match_all('/\.([a-zA-Z_][a-zA-Z0-9_-]*)\(([^)]*)\)/', $chain, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $method = $match[1];
            $args = $this->parseTransformArguments($match[2] ?? '');

            if (!is_object($target) || (!method_exists($target, $method) && !method_exists($target, Val::PRIVATE_PREFIX . $method))) {
                $type = is_object($target) ? get_class($target) : gettype($target);
                throw new \RuntimeException("Method '{$method}' not found on {$type}.");
            }

            $result = $target->$method(...$args);
            if ($result !== null) {
                $target = $result;
            }
        }

        if ($target instanceof IVal) {
            return $target->val();
        }

        return $target;
    }

    protected function getNestedValue($dotNotationString, $varName = null): mixed
    {
        if ($varName !== null && Str::pos($dotNotationString, '.') === false) {
            return $this->getPathValue($varName);
        }

        return $this->getPathValue($dotNotationString);
    }

    public function resolveValue(string $value, ?string $type = null): mixed
    {
        $value = Str::trim($value);
        $firstChar = Str::sub($value, 0, 1);
        $lastChar = Str::sub($value, -1);

        $parsed = match (true) {
            $firstChar === '"' || $firstChar === "'" => trim($value, "'\""),
            $firstChar === '[' => json_decode(str_replace("'", '"', $value), true),
            $firstChar === '{' => json_decode($value, true),
            (bool)preg_match('/^\.[a-zA-Z0-9_]+(?:\.[a-zA-Z0-9_]+)*$/', $value) => $this->getNestedValue("current{$value}", 'current'),
            (bool)preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z0-9_]+)+$/', $value) => $this->getPathValue($value),
            (bool)preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $value) => $this->getScopeVariable($value),
            is_numeric($value) => (float)$value,
            default => $value,
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

    protected function setNestedPathValue(mixed $target, array $segments, mixed $value, string $path): mixed
    {
        $segment = array_shift($segments);

        if ($segment === null) {
            return $value;
        }

        if (empty($segments)) {
            if (is_array($target)) {
                $target[$segment] = $value;
                return $target;
            }

            if ($target instanceof Obj || is_object($target)) {
                $target->$segment = $value;
                return $target;
            }

            throw new \RuntimeException("Undefined value path '{$path}'.");
        }

        if (is_array($target)) {
            if (!array_key_exists($segment, $target)) {
                throw new \RuntimeException("Undefined value path '{$path}'.");
            }

            $target[$segment] = $this->setNestedPathValue($target[$segment], $segments, $value, $path);
            return $target;
        }

        if ($target instanceof Obj) {
            $data = $target->toArray();
            if (!array_key_exists($segment, $data)) {
                throw new \RuntimeException("Undefined value path '{$path}'.");
            }

            $target->$segment = $this->setNestedPathValue($target->$segment, $segments, $value, $path);
            return $target;
        }

        if (is_object($target)) {
            if (!(property_exists($target, $segment) || isset($target->$segment))) {
                throw new \RuntimeException("Undefined value path '{$path}'.");
            }

            $target->$segment = $this->setNestedPathValue($target->$segment, $segments, $value, $path);
            return $target;
        }

        throw new \RuntimeException("Undefined value path '{$path}'.");
    }

    protected function wrapTransformValue(mixed $value): mixed
    {
        if ($value instanceof IVal) {
            return $value;
        }

        return match (true) {
            is_string($value) => Str::make($value),
            is_bool($value) => Flag::make($value),
            is_int($value), is_float($value) => Num::make($value),
            is_array($value) => Arr::make($value),
            default => Val::make($value),
        };
    }

    protected function parseTransformArguments(string $arguments): array
    {
        $arguments = Str::trim($arguments);
        if ($arguments === '') {
            return [];
        }

        preg_match_all('/"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'|\[[^\]]*\]|[^,\s][^,]*/', $arguments, $matches);
        $params = [];

        foreach ($matches[0] ?? [] as $argument) {
            $params[] = $this->resolveValue(Str::trim($argument));
        }

        return $params;
    }
}
