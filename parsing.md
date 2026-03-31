# Parsing & Vibe Templating

DevElation includes a lightweight parsing system used by the Vibe templating language. It powers `BlueFission\Parsing` and is integrated into `BlueFission\HTML\Template` for file-based templates, sections, and includes.

## Quick Start

Use the parser directly for ad-hoc templates:

```php
use BlueFission\Parsing\Parser;
use BlueFission\Parsing\Registry\TagRegistry;
use BlueFission\Parsing\Registry\RendererRegistry;
use BlueFission\Parsing\Registry\ExecutorRegistry;
use BlueFission\Parsing\Registry\PreparerRegistry;

TagRegistry::registerDefaults();
RendererRegistry::registerDefaults();
ExecutorRegistry::registerDefaults();
PreparerRegistry::registerDefaults();

$template = <<<'VIBE'
{#let name="World"}
Hello {$name}
VIBE;

$parser = new Parser($template);
echo $parser->render();
```

Use `BlueFission\HTML\Template` for file-based Vibe layouts:

```php
use BlueFission\HTML\Template;

$template = new Template([
    'file' => 'page.vibe',
    'template_directory' => __DIR__ . '/templates',
    'module_directory' => __DIR__ . '/templates',
]);

$template->assign(['name' => 'World']);
echo $template->render();
```

`Template` can now safely load from either a configured relative file path or an absolute file path:

```php
use BlueFission\HTML\Template;

$template = new Template(__DIR__ . '/templates/page.vibe');
$template->assign(['name' => 'World']);

echo $template->render();
```

Register defaults once per process (for example, during bootstrap) so custom tags and renderers are available everywhere.

Current `#let` typed-value contract:

```vibe
{#let settings:json='{"theme":"dark"}'}
```

Treat `name:type=value` as the supported syntax today. Do not document or depend on `name:type=json = ...` style attribute syntax unless it is separately implemented and covered by tests.

## Phase 1 `src` Loading

The current safe `src` contract is content-oriented:

```vibe
{=data src="notes.txt"}
```

Behavior:

- `src` resolves against existing include paths (`includes`, `modules`, `templates`) and explicit absolute paths.
- the file contents are loaded into the eval result and assigned variable on the direct eval-assignment path.
- JSON files hydrate through the existing value-resolution behavior when the loaded content begins with `{` or `[`.

This is intentionally narrower than a full filesystem contract. It does not yet promise template-native file handles, directory iteration, or `.vibe.gen` execution semantics.

A simple Vibe layout pattern:

```vibe
@template('layout.vibe')
@section('main')
Hello {$name}
@endsection
```

`layout.vibe`:

```vibe
Header:@output('main'):Footer
```

## Core Classes

- `BlueFission\Parsing\Parser` orchestrates parsing and rendering.
- `BlueFission\Parsing\Root` is the root element scope.
- `BlueFission\Parsing\Element` represents a matched tag.
- `BlueFission\Parsing\Block` manages element discovery, scope, and replacement.
- Registries (`TagRegistry`, `RendererRegistry`, `ExecutorRegistry`, `PreparerRegistry`) manage tag definitions and behaviors.

## Tags Supported by Default

Default tags are registered by `TagRegistry::registerDefaults()`:

- Block tags: `#let`, `#if`, `#each`, `#while`, `#await`
- Inline tags: `=`, `$`
- Template tags: `@template`, `@section`, `@output`
- Includes: `@include`, `@import`
- Macros/tools: `@macro`, `@invoke`
- Loop helpers: `{@current}`, `{@index}`, `{.field}`

See `parse.php` and `templates/` for working examples.

## Includes and Templates

- `@template('layout.vibe')` loads a base layout and defers output until sections are resolved.
- `@section('name')...@endsection` captures a section’s content.
- `@output('name')` injects the captured section into the layout.
- `@include('file.vibe')` and `@import('file.vibe')` load external content.

Include search paths are set via `Parser::setIncludePaths()` or by configuring `Template` with `template_directory` and `module_directory`.

## Loop Scope and Nested Composition

Within `#each`, DevElation now exposes the active item as a normal scoped variable named `current`.

That means all of the following are valid inside a loop body:

- `{$current.title}` for explicit scoped access
- `items=current.sections` when a nested include or partial needs to loop child items
- `{@current}` for legacy loop-item output
- `{.title}` as shorthand for the active loop item

Example:

```vibe
{#each items=chapters glue="|"}
{$current.title}
@include('sections.vibe')
{/each}
```

`sections.vibe`:

```vibe
{#each items=current.sections glue=","}{.title}{/each}
```

This is the currently supported growth path for nested iteration. It keeps loop scoping explicit and reusable without forcing deeper same-block recursive parsing changes into the core parser.

## Extending the Parser

To add a new tag:

1) Define a tag in `TagRegistry` using `TagDefinition`.
2) Implement an element (e.g. `Elements\MyTagElement`) to handle attributes and behavior.
3) Register a renderer in `RendererRegistry` to return output.
4) If the tag performs actions, register an executor in `ExecutorRegistry`.
5) If you need parent/paths/context prep, use `PreparerRegistry`.

Example (custom tag):

```php
use BlueFission\Parsing\TagDefinition;
use BlueFission\Parsing\Registry\TagRegistry;
use BlueFission\Parsing\Registry\RendererRegistry;
use BlueFission\Parsing\Renderers\DefaultRenderer;

TagRegistry::register(new TagDefinition(
    name: 'note',
    pattern: '{open}\#note(.*?)?{close}(.*?){open}\/note{close}',
    attributes: ['*'],
    interface: BlueFission\Parsing\Contracts\IRenderableElement::class,
    class: BlueFission\Parsing\Elements\NoteElement::class
));

RendererRegistry::register('note', new DefaultRenderer());
```

## Eval and Generators

`{=...}` uses the generator/dispatcher pipeline to resolve expressions. In tests we stub this via a generator; in real usage register a generator in `GeneratorRegistry` that knows how to resolve tools or LLM-backed functions.

## DevElation Hooks

Parsing classes are hookable via `BlueFission\DevElation`:

- Registries call `Dev::apply()` and `Dev::do()` around registration, lookups, and compiled patterns.
- `Parser`, `Element`, and `Block` expose `_before`/`_after` hooks during parsing and rendering.
- `Block` triggers `parsing.block.before_element` and `parsing.block.after_element` for each tag.

These hooks allow you to instrument parsing, alter attributes, or inject custom behavior without modifying core classes.

## Testing

Parsing tests live under `tests/Parsing` and cover loops, conditionals, templates, and includes. They are designed to be red-green-refactor ready for extending tag behavior.
