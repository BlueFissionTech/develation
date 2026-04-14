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

    protected const ATTRIBUTE_INTERPOLATION_PATTERN = '/\[\[\s*(.*?)\s*\]\]/';

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

        $raw = $this->attributes[$name];
        $value = $this->resolveValue($raw);

        if ($this->shouldInterpolateAttribute($raw, $value)) {
            $value = $this->interpolateAttributeString((string)$value);
        }

        return Dev::apply('_attribute', $value);
    }

    public function getAttributes(): array
    {
        $attributes = [];
        foreach ($this->attributes as $key => $value) {
            $resolved = $this->resolveValue($value);

            if ($this->shouldInterpolateAttribute($value, $resolved)) {
                $resolved = $this->interpolateAttributeString((string)$resolved);
            }

            $attributes[$key] = $resolved;
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
        $segments = Str::make($this->normalizeScopedPath($path))->split('.')->val();
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
        $segments = Str::make($this->normalizeScopedPath($path))->split('.')->val();
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
        $segments = Str::make($this->normalizeScopedPath($path))->split('.')->val();
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

        if (preg_match('/^(?<path>\@?[a-zA-Z_][a-zA-Z0-9_.-]*)\s*->\s*\$(?<chain>(?:\.[a-zA-Z_][a-zA-Z0-9_-]*\([^)]*\))+)$/', $expression, $matches)) {
            $result['path'] = $matches['path'];
            $result['chain'] = $matches['chain'];
            $result['clone'] = true;

            return $result;
        }

        if (preg_match('/^(?<path>\@?[a-zA-Z_][a-zA-Z0-9_.-]*?)(?<chain>(?:\.[a-zA-Z_][a-zA-Z0-9_-]*\([^)]*\))+)$/', $expression, $matches)) {
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
        $dotNotationString = $this->normalizeScopedPath($dotNotationString);

        if ($varName !== null && Str::pos($dotNotationString, '.') === false) {
            return $this->getPathValue($this->normalizeScopedPath($varName));
        }

        return $this->getPathValue($dotNotationString);
    }

    protected function normalizeScopedPath(string $path): string
    {
        $path = Str::trim($path);

        if (!Str::startsWith($path, '@')) {
            return $path;
        }

        return Str::sub($path, 1);
    }

    protected function shouldInterpolateAttribute(mixed $raw, mixed $value): bool
    {
        if (!Str::is($raw) || !Str::is($value)) {
            return false;
        }

        $raw = Str::trim((string)$raw);
        $firstChar = Str::sub($raw, 0, 1);
        $lastChar = Str::sub($raw, -1);

        if (!(($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'"))) {
            return false;
        }

        return Str::has((string)$value, '[[');
    }

    protected function interpolateAttributeString(string $value): string
    {
        if (!Str::has($value, '[[')) {
            return $value;
        }

        $interpolated = preg_replace_callback(
            self::ATTRIBUTE_INTERPOLATION_PATTERN,
            function (array $matches): string {
                $expression = Str::trim((string)($matches[1] ?? ''));

                if ($expression === '') {
                    return '';
                }

                return $this->resolveInterpolationExpression($expression);
            },
            $value
        );

        $interpolated = Dev::apply('parsing.element.interpolate_attribute', $interpolated);

        Dev::do('parsing.element.interpolate_attribute.action1', [
            'element' => $this,
            'value' => $value,
            'interpolated' => $interpolated,
        ]);

        return (string)$interpolated;
    }

    protected function resolveInterpolationExpression(string $expression): string
    {
        $segments = Str::make($expression)->split('|')->val();
        $base = Str::trim((string)array_shift($segments));
        $value = $this->resolveInterpolationValue($base);

        foreach ($segments as $segment) {
            $segment = Str::trim((string)$segment);

            if ($segment === '') {
                continue;
            }

            [$filter, $arguments] = $this->parseInterpolationFilter($segment);
            $value = $this->applyInterpolationFilter($value, $filter, $arguments);
        }

        return $this->stringifyInterpolatedValue($value);
    }

    protected function resolveInterpolationValue(string $expression): mixed
    {
        if ($expression === '') {
            return '';
        }

        if ((bool)preg_match('/^(["\']).*\\1$/', $expression)) {
            return Str::trim($expression, '\'"');
        }

        return $this->resolveValue($expression);
    }

    protected function parseInterpolationFilter(string $segment): array
    {
        $parts = Str::make($segment)->split(':')->val();
        $filter = Str::lower(Str::trim((string)array_shift($parts)));

        if ($filter === 'default' && count($parts) > 1) {
            $parts = [implode(':', $parts)];
        }

        $arguments = [];
        foreach ($parts as $argument) {
            $argument = Str::trim((string)$argument);
            $arguments[] = $this->normalizeInterpolationArgument($argument);
        }

        return [$filter, $arguments];
    }

    protected function applyInterpolationFilter(mixed $value, string $filter, array $arguments = []): mixed
    {
        return match ($filter) {
            'slug', 'slugify' => Str::slugify($this->stringifyInterpolatedValue($value)),
            'lower' => Str::lower($this->stringifyInterpolatedValue($value)),
            'upper' => Str::upper($this->stringifyInterpolatedValue($value)),
            'trim' => Str::trim($this->stringifyInterpolatedValue($value)),
            'pad' => $this->applyPadInterpolationFilter($value, $arguments),
            'default' => $this->applyDefaultInterpolationFilter($value, $arguments),
            default => $value,
        };
    }

    protected function applyPadInterpolationFilter(mixed $value, array $arguments = []): string
    {
        $string = $this->stringifyInterpolatedValue($value);
        $length = isset($arguments[0]) && is_numeric($arguments[0]) ? (int)$arguments[0] : 2;
        $pad = isset($arguments[1]) && $arguments[1] !== '' ? (string)$arguments[1] : '0';
        $direction = isset($arguments[2]) && Str::lower((string)$arguments[2]) === 'right'
            ? STR_PAD_RIGHT
            : STR_PAD_LEFT;

        return str_pad($string, $length, $pad, $direction);
    }

    protected function applyDefaultInterpolationFilter(mixed $value, array $arguments = []): mixed
    {
        $default = $arguments[0] ?? '';

        if ($value === null) {
            return $default;
        }

        if (Str::is($value) && Str::trim((string)$value) === '') {
            return $default;
        }

        return $value;
    }

    protected function normalizeInterpolationArgument(string $argument): mixed
    {
        if ($argument === '') {
            return '';
        }

        if ((bool)preg_match('/^(["\']).*\\1$/', $argument)) {
            return Str::trim($argument, '\'"');
        }

        return $argument;
    }

    protected function stringifyInterpolatedValue(mixed $value): string
    {
        if ($value instanceof IVal) {
            $value = $value->val();
        }

        if ($value instanceof Obj) {
            $value = $value->toArray();
        }

        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        if (is_array($value)) {
            $stringified = [];
            foreach ($value as $item) {
                $stringified[] = $this->stringifyInterpolatedValue($item);
            }

            return implode(',', $stringified);
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string)$value;
        }

        return '';
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
            (bool)preg_match('/^\@[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z0-9_]+)*$/', $value) => $this->getPathValue($this->normalizeScopedPath($value)),
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

        if (is_object($value)) {
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
