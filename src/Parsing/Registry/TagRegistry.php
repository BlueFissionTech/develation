<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\TagDefinition;
use BlueFission\Parsing\Elements;
use BlueFission\Parsing\Contracts;

class TagRegistry {

    const ROOT = '__ROOT__';

    protected static array $definitions = [];

    public static function register(TagDefinition $definition): void {
        self::$definitions[$definition->name] = $definition;
    }

    public static function all(): array {
        return self::$definitions;
    }

    public static function get(string $name): ?TagDefinition {
        return self::$definitions[$name] ?? null;
    }

    public static function patterns(string $open = '{', string $close = '}'): array {
        $compiled = [];
        foreach (self::all() as $tag => $def) {
            $pattern = str_replace(['{open}', '{close}'], [preg_quote($open, '/'), preg_quote($close, '/')], $def->pattern);
            $compiled[$tag] = $pattern;
        }
        return $compiled;
    }

    public static function unifiedPattern(string $open = '{', string $close = '}'): string {
        $parts = [];
        foreach (self::all() as $tag => $def) {
            if ($def->pattern) {
                $pattern = str_replace(['{open}', '{close}'], [preg_quote($open, '/'), preg_quote($close, '/')], $def->pattern);
                $part = "(?P<{$tag}>{$pattern})";
                if (@preg_match('/' . $part . '/sx', '') === false) {
                    throw new \RuntimeException("Invalid regex part: $part");
                }
                $parts[] = $part;
            }
        }
        return '/' . implode('|', $parts) . '/sx';
    }

    public static function tagPattern(): string {
        return '/
            (?<full_tag>                                        # entire tag block
                (?<tag_open>\{[#=\$]) ?                         # open brace + type
                (?<tag_name>[a-zA-Z_][a-zA-Z0-9_-]*)?            # tag or variable name
                (?:\((?<function_args>[^)]*)\))?                 # optional (function args)
                (?:\s*->\s*(?<assign_target>[a-zA-Z_][a-zA-Z0-9_-]*))? # optional -> assign
                (?<attributes>                                   # attributes blob
                    (?:
                        \s+[a-zA-Z_][a-zA-Z0-9_-]*               # key
                        \s*=\s*
                        (?:
                            "(?:[^"\\\\]*(?:\\\\.[^"\\\\]*)*)"   # double quoted
                            |
                            \'(?:[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\' # single quoted
                            |
                            \[[^\]]*\]                            # bracketed
                            |
                            [^\s"\'\]\}]+                         # unquoted
                        )
                    )*
                )?
                \}                                               # close of opening tag

                (?<inner_content>                                # optional block content
                    (?:
                        .*?                                      # non-greedy content match
                    )
                )?
            (\{\\\k<tag_name>\})                                 # lookahead for matching close
            )
        /xs';
    }

    public static function extractAttributes(string $tag, array $match): array {
        $attributes = [];

        $definition = self::get($tag);
        if (!$definition || empty($definition->attributes)) {
            return $attributes;
        }

        // Raw tag body from the named capture group
        $raw = $match[$tag][0] ?? '';

        // Remove outer delimiters if they exist (e.g., {#if ...}, @template(...))
        $raw = trim($raw);

        // Extract inner expression — e.g. from {#if var="x"} or @template("main")
        if (preg_match('/^[{@]?\#?[a-z]+\s*(?:\((.*?)\))?/i', $raw, $inner)) {
            $argString = $inner[1] ?? '';

            // If the tag uses parenthetical single-arg syntax, like @template("x")
            if ($argString && count($definition->attributes) === 1) {
                $attributes[$definition->attributes[0]] = $argString;

                return $attributes;
            }
        }


        // Remove tag name prefix (e.g., #if, @template) to leave only key=val attrs
        $clean = preg_replace('/^[{@]?#?' . preg_quote($tag, '/') . '\s*/i', '', $raw);
        $clean = preg_replace('/[{}]$/', '', $clean); // trailing brace

        preg_match('/
            ^[{@]?
            (?<tag_open>[\#=\$])\s*?                            # tag type
            (?<tag_name>\$?[a-zA-Z_][a-zA-Z0-9_-]*)          # tag or variable
            (?:\((?<function_args>[^)]*)\))?               # optional func args
            (?:\s*->\s*(?<assign_target>[a-zA-Z_][a-zA-Z0-9_-]*))? # optional assignment
            (?<raw_attributes>.*)?                          # the rest = attributes
        /x', $raw, $meta);

        $attributeStr = $meta['raw_attributes'] ?? '';

        // Match key="value", key='value', or key=value
        preg_match_all('/
            (?<key>[a-zA-Z_][a-zA-Z0-9_-]*)              # key
            \s*=\s*
            (?<value>
                (?<double_quoted>"(.*?)")                            # double-quoted
                |
                (?<single_quoted>\'(.*?)\')                          # single-quoted
                |
                (?<bracketed>\[(.*?)\])                          # bracketed
                |
                (?<unquoted>[^\s"\'\]\}]+)                    # unquoted
            )
        /x', $attributeStr, $matches, PREG_SET_ORDER);

        $tag_name = $meta['tag_name'] ?? $tag;
        $function_args = $meta['function_args'] ?? '';
        $assign_target = $meta['assign_target'] ?? '';

        if (isset($meta['tag_open']) && $meta['tag_open'] === '=') {
            // If it's a variable tag, we only care about the variable name
            $attributes['expression'] = $tag_name;
            if ($assign_target !== '') {
                $attributes['assign'] = $assign_target;
            }
            if ($function_args !== '') {
                $attributes['params'] = $function_args;
            }
        }

        if (isset($meta['tag_open']) && $meta['tag_open'] === '$') {
            // If it's a variable tag, we only care about the variable name
            $attributes['name'] = $tag_name;
        }

        foreach ($matches as $m) {
            $key = $m['key'];
            if ($definition->attributes[0] == '*' || in_array($key, $definition->attributes)) {
                $value =  $m['value'];
                $attributes[$key] = $value;
            }
        }

        // Fallback: if a single unnamed parameter was passed (and allowed)
        if (empty($attributes) && count($definition->attributes) === 1) {
            $attributes[$definition->attributes[0]] = $clean;
        }

        return $attributes;
    }

    public static function registerDefaults() {
        self::register(new TagDefinition(
            name: 'if',
            pattern: '{open}\#if(.*?)?{close}(.*?){open}\/if{close}',
            attributes: ['var', 'equals', 'not_equals', 'gt', 'lt', 'gte', 'lte'],
            interface: Contracts\IConditionElement::class,
            class: Elements\IfElement::class
        ));

        self::register(new TagDefinition(
            name: 'each',
            pattern: '{open}\#each(.*?)?{close}(.*?){open}\/each{close}',
            attributes: ['items', 'iterations', 'glue'],
            interface: Contracts\ILoopElement::class,
            class: Elements\EachElement::class
        ));

        self::register(new TagDefinition(
            name: 'let',
            pattern: '{open}\#let (.*?)=(.*?){close}',
            attributes: ['*'],
            interface: Contracts\IExecutableElement::class,
            class: Elements\LetElement::class
        ));

        self::register(new TagDefinition(
            name: 'eval',
            pattern: '{open}=(.*?)(?:->(\\w+))?(?:\\s+silent=[\'\"]?(true|false)[\'\"]?)?{close}',
            attributes: ['expression', 'params', 'assign', 'silent', 'default'],
            interface: Contracts\IRenderableElement::class,
            class: Elements\EvalElement::class
        ));

        self::register(new TagDefinition(
            name: 'var',
            pattern: '{open}\\$(\\w+){close}',
            attributes: ['name'],
            interface: Contracts\IRenderableElement::class,
            class: Elements\VarElement::class
        ));

        self::register(new TagDefinition(
            name: 'while',
            pattern: '{open}\#while(.*?)?{close}(.*?){open}\/while{close}',
            attributes: ['var', 'equals', 'not_equals', 'gt', 'lt', 'gte', 'lte'],
            interface: Contracts\ILoopElement::class,
            class: Elements\WhileElement::class
        ));

        self::register(new TagDefinition(
            name: 'await',
            pattern: '{open}\#await(.*?)?{close}(.*?){open}\/await{close}',
            attributes: ['event'],
            interface: Contracts\IExecutableElement::class,
            class: Elements\AwaitElement::class
        ));

        self::register(new TagDefinition(
            name: 'template',
            pattern: '\@template\((.*?)\)',
            attributes: ['name'],
            interface: Contracts\IRenderableElement::class,
            class: Elements\TemplateElement::class
        ));

        self::register(new TagDefinition(
            name: 'section',
            pattern: '\@section\((.*?)\)(.*?)\@endsection',
            attributes: ['name'],
            interface: Contracts\IRenderableElement::class,
            class: Elements\SectionElement::class
        ));

        self::register(new TagDefinition(
            name: 'output',
            pattern: '\@output\((.*?)\)',
            attributes: ['name'],
            interface: Contracts\IRenderableElement::class,
            class: Elements\OutputElement::class
        ));

        self::register(new TagDefinition(
            name: 'mod',
            pattern: '@mod\((.*?)\)',
            attributes: ['name'],
            interface: Contracts\IRenderableElement::class,
            class: Elements\ModElement::class
        ));

        self::register(new TagDefinition(
            name: 'import',
            pattern: '@import\((.*?)\)',
            attributes: ['name'],
            interface: Contracts\IRenderableElement::class,
            class: Elements\ImportElement::class
        ));

        self::register(new TagDefinition(
            name: 'macro',
            pattern: '\@macro\((.*?)\)(.*?)\@endmacro',
            attributes: ['name'],
            interface: Contracts\IRenderableElement::class,
            class: Elements\MacroElement::class
        ));

        self::register(new TagDefinition(
            name: 'invoke',
            pattern: '@invoke\((.*?)\)',
            attributes: ['name'],
            interface: Contracts\IExecutableElement::class,
            class: Elements\MacroElement::class
        ));

        self::register(new TagDefinition(
            name: 'current',
            pattern: '{open}@current\.?(.*?){close}',
            attributes: ['name'],
            interface: Contracts\IRenderableElement::class,
            class: Elements\CurrentElement::class
        ));

        self::register(new TagDefinition(
            name: 'index',
            pattern: '{open}@index{close}',
            attributes: ['name'],
            interface: Contracts\IRenderableElement::class,
            class: Elements\IndexElement::class
        ));
    }
}
