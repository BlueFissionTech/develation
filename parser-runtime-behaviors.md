# Parser Runtime Behaviors

This document records the current parser/runtime lifecycle in DevElation and proposes a non-breaking path toward a clearer Behavior-native execution surface for parser- and interpreter-style runtimes.

The goal is not to redesign the parser. The goal is to make its lifecycle more explicit, more traceable, and easier for downstream language runtimes to observe and extend through DevElation-native patterns.

## Why This Matters

Today, `BlueFission\Parsing` already exposes several useful extension points:

- `Dev::do(...)` and `Dev::apply(...)` hooks around parsing, rendering, evaluation, and registry access
- `Dispatches`-based event bubbling between parser objects
- preparers, renderers, executors, generators, functions, and registries

That is enough to build flexible parser-driven runtimes. It is not yet enough to present a stable, documented lifecycle surface that downstream interpreters can treat as a first-class runtime contract.

Downstream libraries want:

- stable lifecycle phases
- better event/state vocabulary
- richer metadata during execution
- replay/debug tracing
- a non-breaking growth path

## Current Runtime Surface

### Parser

`BlueFission\Parsing\Parser` currently:

- creates the root scope
- echoes a small set of dispatched events from the root
- wraps render with generic `_before` / `_after` hooks

Current observable signals:

- `Dev::do('_before', [$input, $open, $close])`
- `Dev::do('_after', [$this->root])`
- `Dev::do('_before', [$this])`
- `Dev::do('_after', [$output, $this])`
- echoed dispatches: `Event::SENT`, `Event::RECEIVED`, `Event::ERROR`, `Event::ITEM_ADDED`, `State::RUNNING`, `State::IDLE`

### Root / Element

`Root` and `Element` currently:

- maintain scope variables and include paths
- own a `Block`
- expose attribute/value resolution
- render nested content and templates

Current observable signals:

- generic `_before` / `_after` hooks around rendering
- `Dev::apply('_attribute', ...)`
- `Dev::apply('_attributes', ...)`
- `Dev::apply('_value', ...)`
- `Event::ITEM_ADDED` echoing from child blocks

### Block

`BlueFission\Parsing\Block` currently:

- discovers elements
- extracts attributes
- prepares elements
- processes elements into output

Current observable signals:

- `_before` / `_after` hooks around parse and process
- `Event::ITEM_ADDED` for discovered elements
- `State::PROCESSING`
- `Event::PROCESSED`
- `parsing.block.before_element`
- `parsing.block.after_element`

### Evaluator

`BlueFission\Parsing\Evaluator` currently:

- parses an evaluation expression
- resolves assignments, casts, pushes, appends, tools, generators, and `src`
- writes results back into scope

Current observable signals:

- `_before` / `_after` hooks around evaluation
- generator echo registration when supported
- implicit behavior through downstream generator/function implementations

### Registries and Supporting Components

Registries and auxiliary runtime pieces already expose hooks around:

- tag definitions
- functions
- generators
- executors
- processors
- preparers

This is strong infrastructure, but the lifecycle vocabulary is still fragmented across generic hooks, generic states, and downstream conventions.

## Current Gaps

The parser is extensible, but it does not yet present a clean runtime contract.

Main gaps:

- lifecycle phases are implied, not formalized
- most hooks use generic names like `_before` / `_after`
- only a few dispatched events carry parser-specific meaning
- there is no standard runtime metadata envelope for element, expression, or scope context
- there is no built-in replay/debug trace model
- downstream runtimes still need private adapters to recover lifecycle meaning

## Lifecycle Phases Worth Formalizing

The following phases already exist conceptually and should be formalized first.

### 1. Bootstrap

Meaning:

- parser/root construction
- template/load context setup
- include path setup
- initial variable binding

Current signals:

- parser/root `_before` and `_after`

Recommended additive runtime surface:

- `OnRuntimeBooting`
- `OnRuntimeBooted`
- `IsInitializing`

### 2. Parse / Discover

Meaning:

- regex matching
- block extraction
- balanced loop detection
- element instantiation

Current signals:

- block parse hooks
- `Event::ITEM_ADDED`

Recommended additive runtime surface:

- `OnParseStarted`
- `OnElementDiscovered`
- `OnParseCompleted`
- `IsParsing`

### 3. Prepare

Meaning:

- hierarchy, path, variable, and event-bubble preparers
- parent/context binding

Current signals:

- preparer-level hooks

Recommended additive runtime surface:

- `OnElementPreparing`
- `OnElementPrepared`
- `IsPreparing`

### 4. Execute / Generate

Meaning:

- executable tags
- evaluator assignment flows
- tools/functions
- generators
- import/include side effects

Current signals:

- evaluator `_before` / `_after`
- executor/generator hooks

Recommended additive runtime surface:

- `OnEvaluationStarted`
- `OnEvaluationCompleted`
- `OnGeneratorInvoked`
- `OnToolInvoked`
- `OnImportResolved`
- `OnIncludeResolved`
- `IsExecuting`
- `IsGenerating`

### 5. Render / Replace

Meaning:

- renderer output
- loop output assembly
- content replacement into the parent block

Current signals:

- renderer hooks
- `parsing.block.before_element`
- `parsing.block.after_element`
- block processed event

Recommended additive runtime surface:

- `OnRenderStarted`
- `OnElementRendered`
- `OnRenderCompleted`
- `IsRendering`

### 6. Validate / Policy

Meaning:

- runtime guardrails
- attribute policy
- schema or contract checks
- optional downstream policy enforcement

Current signals:

- mostly downstream/private today

Recommended additive runtime surface:

- `OnValidationStarted`
- `OnValidationFailed`
- `OnValidationCompleted`
- `IsValidating`

### 7. Trace / Replay

Meaning:

- debug history
- runtime inspection
- deterministic execution review

Current signals:

- none formalized

Recommended additive runtime surface:

- `OnTraceRecorded`
- `OnReplayStarted`
- `OnReplayCompleted`
- `IsTracing`
- `IsReplaying`

## Recommended Additive Surface

The safest path is to add a parser-runtime behavior layer beside the existing parser, not inside it as a sweeping rewrite.

### Runtime Event Vocabulary

Add parser-specific behavior constants under a parsing namespace, for example:

- `BlueFission\Parsing\Behaviors\RuntimeEvent`
- `BlueFission\Parsing\Behaviors\RuntimeState`

These should wrap stable names for the phases above instead of overloading the generic application-wide event catalog.

This matters because parser runtimes need terms like:

- parse
- discover
- prepare
- evaluate
- generate
- render
- validate
- trace
- replay

Those concepts are more specific than the current generic `OnProcessed`, `OnStarted`, or `IsRunning`.

### Public Naming Grammar

The PHP classes can stay PHP-native. The runtime-visible names should not.

Recommended split:

- PHP class names: `RuntimeEvent`, `RuntimeState`, `RuntimeAction`, `RuntimeMeta`
- emitted/runtime-visible names: lowercase, namespaced, and hierarchy-oriented

Recommended public grammar:

- events: `on:<domain>.<phase>`
- states: `is:<domain>.<state>`
- actions: `do:<domain>.<action>`
- metadata keys: lowercase dotted keys such as `run.id`, `element.uuid`, `assignment.target`

Examples:

- `on:parse.start`
- `on:parse.complete`
- `on:element.discover`
- `on:evaluate.complete`
- `on:render.complete`
- `is:parsing`
- `is:rendering`
- `is:tracing`
- `do:trace.record`
- `do:policy.validate`

This aligns better with:

- Vibe’s lowercase authored surface
- colon-based namespacing already familiar from tags like `mix:scene`
- dot-based property paths already familiar from `current.title`, `data.schema.name`, and `[[book.slug|slug]]`
- the existing intentional Blue Fission behavioral prefixes `On`, `Is`, and `Do`, translated into language-native runtime strings as `on:`, `is:`, and `do:`

### Naming Rules

Recommended public/runtime naming rules:

1. Use lowercase only.
2. Use one reserved prefix family: `on:`, `is:`, `do:`.
3. Use dot segments after the prefix to express hierarchy.
4. Keep event names phase-oriented, not implementation-oriented.
5. Keep metadata keys dotted and structural.
6. Keep PHP constant names stable even if public runtime strings evolve.

Examples:

- good: `on:include.resolve`
- good: `on:policy.validate.fail`
- good: `is:runtime.booting`
- bad: `OnIncludeResolved`
- bad: `ParserIncludeResolved`
- bad: `include_resolved`

### Alignment With Vibe Syntax

This naming system should inform adjacent runtime-facing surfaces over time:

- reserved runtime values: `@current`, `@index`, later possibly `@runtime.phase`, `@trace.id`, `@element.uuid`
- metadata keys and trace payloads: `trace.id`, `run.id`, `source.path`, `scope.keys`
- runtime-facing attribute names where behavior is exposed declaratively
- policy/filter routing names in downstream interpreters

It should not force PHP class names, method names, or file names to abandon normal PHP conventions.

## Naming Collisions and Early Carve-Outs

If we adopt public runtime names too casually, we will lock ourselves in at the wrong layer.

The main risk is not parser syntax. The main risk is downstream contract shape.

### Collision 1: PHP Class Names vs Runtime Names

Risk:

- using PHP-style runtime names like `OnParseStarted` in public payloads makes trace data and policy hooks feel like PHP internals instead of language/runtime constructs

Early carve-out:

- keep PHP class names conventional
- treat emitted values as a separate authored/runtime grammar
- never assume constant name and emitted string must match stylistically

### Collision 2: Colon Namespaces vs Tag Namespaces

Risk:

- Vibe already uses colon in tag namespaces such as `mix:scene`
- if runtime names also use colons, we must avoid making them look like element names

Early carve-out:

- reserve `on:`, `is:`, and `do:` exclusively for behavior identifiers
- reserve tag namespaces like `mix:`, `html:`, `spec:` for authored elements/directives
- document that behavior identifiers are values, not tags

This preserves a readable distinction:

- tag: `mix:scene`
- event: `on:scene.enter`

### Collision 3: Dot Paths vs Metadata Keys vs Variable Paths

Risk:

- dot notation already means property access in Vibe and parser expressions
- if metadata and events also use dots, tools may confuse a key path with a runtime event name

Early carve-out:

- only allow dots after a reserved behavior prefix in behavior identifiers
- treat metadata keys as payload keys, not expressions
- avoid using bare dotted names with no prefix for runtime events

Examples:

- variable path: `current.seed.title`
- metadata key: `assignment.target`
- event name: `on:evaluate.complete`

These remain visually related but grammatically distinct.

### Collision 4: Reserved Runtime Values vs User Variables

Risk:

- new reserved values like `@runtime.phase` or `@trace.id` can collide conceptually with user-defined values named `runtime` or `trace`

Early carve-out:

- keep all engine-reserved runtime values behind `@`
- continue allowing bare user variables like `runtime` or `trace`
- do not silently reserve bare names that are not prefixed

This matches the direction already reinforced by `@current` and `@index`.

### Collision 5: Behavior Phases vs Implementation Details

Risk:

- names like `on:block.before_element` or `on:evaluator.process.start` leak current class boundaries into the public contract
- that makes refactoring harder because internal class names become public API

Early carve-out:

- name public behaviors after conceptual lifecycle phases, not classes
- keep implementation-specific hooks internal

Prefer:

- `on:parse.start`
- `on:element.discover`
- `on:evaluate.complete`

Avoid:

- `on:block.parse.start`
- `on:evaluator.process.start`
- `on:default-renderer.after`

### Collision 6: Roadmap Pressure From Vibrato

`vibe-interpreter` is already moving toward:

- units
- interfaces
- actions
- views
- flows
- policies
- specs
- mix namespaced tags
- strict vs loose runtime modes
- project-scale generation and review flows

Risk:

- if parser runtime names are too parser-specific, they will not scale to interpreter/runtime orchestration
- if they are too generic, they will become vague and unusable for debugging or policy enforcement

Early carve-out:

- keep the first lifecycle vocabulary parser/runtime-oriented, but domain-neutral enough to sit beneath Vibrato features
- avoid hard-coding content-generation assumptions into the core names

Good foundational names:

- `on:parse.start`
- `on:prepare.complete`
- `on:evaluate.start`
- `on:render.complete`
- `on:validate.fail`
- `on:trace.record`

These can sit below later higher-order interpreter events such as:

- `on:unit.compile`
- `on:policy.apply`
- `on:flow.transition`
- `on:spec.serialize`

### Collision 7: HTML/XML Influence Can Help, but Also Mislead

HTML/XML conventions are useful here because they reinforce:

- lowercase authored syntax
- colon namespace families
- hierarchical naming

But there is a trap:

- DOM events are often imperative and UI-centric
- XML namespaces are usually static type markers, not lifecycle markers

Early carve-out:

- borrow the readability, not the whole model
- let Vibe/runtime names feel authored and namespaced
- do not overfit them to DOM event habits or XML schema naming

## Roadmap Guidance

To avoid premature lock-in, the roadmap should explicitly separate:

1. Internal PHP implementation names
2. Public runtime-emitted identifiers
3. Future interpreter-level lifecycle names

Recommended rule:

- parser/runtime layer defines the base lifecycle grammar
- Vibrato and later runtimes build on it, but do not rename it ad hoc
- any public identifier introduced now must be assumed durable in traces, policies, tests, and integrations

## Provisions to Carve Out Now

These should be stated early so future work has room to move.

1. Public runtime names may have aliases during early rollout.
2. PHP constant names and emitted runtime strings are intentionally different layers.
3. Reserved runtime values always use `@`.
4. Behavior identifiers always use `on:`, `is:`, or `do:`.
5. Bare dotted identifiers are never behavior identifiers.
6. Public lifecycle names describe concepts, not classes.
7. Future interpreter-specific domains may extend the grammar, but should not break the base parser/runtime prefixes.

### Runtime Metadata Envelope

Add a parser/runtime-specific metadata object, likely extending or complementing `Behavioral\Behaviors\Meta`.

Recommended fields:

- `runId`
- `phase`
- `tag`
- `elementClass`
- `elementUuid`
- `parentUuid`
- `match`
- `raw`
- `scopeKeys`
- `expression`
- `assignmentTarget`
- `includePath`
- `sourcePath`
- `driver`
- `success`
- `message`
- `durationMs`
- `timestamp`
- `exceptionClass`
- `exceptionMessage`

This should be additive and optional. Existing listeners must not be forced to consume a new shape.

### Runtime Observer / Adapter

Do not convert `Parser`, `Element`, `Block`, and `Evaluator` directly to `Behaves` in one step.

Instead, introduce an adapter surface such as:

- `ParserRuntimeObserver`
- `ParserRuntimeTrace`
- `BehavioralRuntimeBridge`

That layer can:

- subscribe to existing hooks/dispatches
- emit parser-specific behaviors
- collect trace entries
- expose replay/debug material

This keeps the current parser stable while giving downstream runtimes a consistent event model.

## Replay / Debug Requirements

The runtime trace should be:

- append-only during a run
- serializable
- cheap to disable
- stable in ordering
- safe to redact

Minimum trace entry shape:

- lifecycle phase
- element/tag identity
- input summary
- output summary
- timing
- success/failure
- scope mutation summary

Important constraints:

- do not capture full scope by default if that would be too heavy or sensitive
- do not make tracing mandatory for normal render paths
- do not mix replay trace storage directly into core parser state unless the caller opts in

## What Should Not Happen

The wrong next step would be a large refactor that replaces the parser’s current extension model.

Avoid:

- replacing `Dev::do` / `Dev::apply`
- replacing `Dispatches` across the parser in one pass
- forcing downstream runtimes to adopt new behavior names immediately
- making tracing always-on
- pushing parser-specific assumptions into generic `Behavioral` contracts

## Recommended Phased Rollout

### Phase 1: Vocabulary and Metadata

Add:

- parser-specific event/state constants
- parser runtime metadata class
- documentation of lifecycle phases

No behavioral change should be required in this phase.

### Phase 2: Runtime Bridge

Add:

- an opt-in runtime observer/bridge that listens to current hooks/dispatches
- trace entry collection
- explicit parser runtime events emitted from the bridge

This is the safest first implementation phase.

### Phase 3: Direct Emission at Key Boundaries

Once the bridge vocabulary is stable, add direct parser/runtime emissions at a few high-value points:

- block parse start/end
- element discovery
- evaluator start/end
- renderer output
- include/import resolution

Keep old hooks in place.

### Phase 4: Optional Behavior-Native Runtime Profiles

If downstream adoption proves the model, add opt-in runtime classes or traits that are more explicitly Behavior-native.

Examples:

- a behavior-aware parser wrapper
- a trace-enabled interpreter runtime base
- policy/validation middleware for parser execution

This phase should remain additive and optional.

## Advanced Use Cases

The following use cases show how a formal parser/runtime lifecycle can support real applications without changing Vibe syntax or breaking current parser consumers.

Each use case includes:

- the application or runtime type
- the behaviors/events that matter most
- an acceptance story
- a concrete Vibe or runtime example

### 1. Editorial Publishing Pipeline

Application:

- long-form article, chapter, glossary, or book assembly

Why the runtime lifecycle matters:

- publishers need provenance
- editors need section-level traceability
- generation and authored content must be distinguishable

Key runtime behaviors:

- `OnParseStarted`
- `OnElementDiscovered`
- `OnEvaluationCompleted`
- `OnGeneratorInvoked`
- `OnRenderCompleted`
- `OnTraceRecorded`

Acceptance story:

- As an editorial runtime, I want every generated chapter block and imported glossary section to emit lifecycle metadata, so that I can audit what was authored, imported, and generated in a single render pass.

Example Vibe template:

```vibe
{#let book='{"slug":"resilient-systems"}'}
{#let chapter=3}
{#let glossaryEntries=[]}

{=chapterBlueprint
    -> generatedChapter
    thread="book:[[book.slug|slug]]:chapter:[[chapter|pad:2]]"
    label="Chapter [[chapter|pad:2]]"
}

{=captureGlossary(generatedChapter) -> glossaryEntries[] silent=true}

@include('chapter-layout.vibe')
```

Example runtime observer intent:

```php
$runtime->when('OnGeneratorInvoked', function ($meta) use ($auditLog) {
    $auditLog[] = [
        'phase' => $meta->phase,
        'tag' => $meta->tag,
        'thread' => $meta->data['thread'] ?? null,
        'assignmentTarget' => $meta->assignmentTarget,
    ];
});
```

### 2. CLI Runbook / Agent Orchestration

Application:

- CLI task manifests
- command runbooks
- agent workflow shells

Why the runtime lifecycle matters:

- a CLI runtime needs deterministic tracing
- tool calls must be observable
- replay is more important than rich rendering

Key runtime behaviors:

- `OnRuntimeBooted`
- `OnValidationStarted`
- `OnToolInvoked`
- `OnEvaluationCompleted`
- `OnReplayStarted`
- `IsExecuting`

Acceptance story:

- As a CLI runtime, I want tool invocations and assignment writes to be traced in a stable order, so that I can replay a failed runbook with the same inputs and inspect the exact step that failed.

Example Vibe template:

```vibe
{#let branch="issue/75-assess-behavior-native-runtime"}
{#let report=[]}

{=checkBranch(branch) -> branchState silent=true}
{=collectChecks(branchState) -> report[] silent=true}
{=renderSummary(report) -> output}

{$output}
```

Example runtime observer intent:

```php
$runtime->when('OnToolInvoked', function ($meta) use ($trace) {
    $trace->record([
        'phase' => $meta->phase,
        'expression' => $meta->expression,
        'assignmentTarget' => $meta->assignmentTarget,
        'timestamp' => $meta->timestamp,
    ]);
});
```

### 3. Multi-tenant Prompt / Chat Session Assembly

Application:

- chatbot prompt composition
- tenant-specific support policies
- conversation-state rendering

Why the runtime lifecycle matters:

- prompt fragments come from multiple modules
- tenant overrides must be visible
- runtime validation should block missing session context

Key runtime behaviors:

- `OnIncludeResolved`
- `OnImportResolved`
- `OnValidationFailed`
- `OnEvaluationStarted`
- `OnRenderCompleted`

Acceptance story:

- As a prompt runtime, I want included policy fragments, imported memory blocks, and final prompt renders to emit structured lifecycle metadata, so that I can debug tenant overrides without patching prompt tags downstream.

Example Vibe template:

```vibe
{#let tenant='{"slug":"acme-support"}'}
{#let session='{"id":"sess_42","tier":"gold"}'}

@import('memory.vibe')
@include('base-policy.vibe')
@include('tenant-policy.vibe')

{=supportPrompt
    -> finalPrompt
    thread="tenant:[[tenant.slug|slug]]:session:[[session.id]]"
    label="Support session [[session.id]]"
}

{$finalPrompt}
```

Example runtime observer intent:

```php
$runtime->when('OnIncludeResolved', function ($meta) use ($resolverLog) {
    $resolverLog[] = [
        'includePath' => $meta->includePath,
        'parentUuid' => $meta->parentUuid,
        'tag' => $meta->tag,
    ];
});
```

### 4. Adaptive Learning / Curriculum Rendering

Application:

- lesson plans
- quizzes
- adaptive curriculum modules

Why the runtime lifecycle matters:

- selected content needs to remain explainable
- generated hints must be traceable
- different learner states should be replayable

Key runtime behaviors:

- `OnParseCompleted`
- `OnElementPrepared`
- `OnGeneratorInvoked`
- `OnValidationCompleted`
- `OnTraceRecorded`

Acceptance story:

- As a learning runtime, I want selected lesson branches, generated hints, and validation checkpoints to be traceable, so that an instructor can review how a learner-specific lesson was assembled.

Example Vibe template:

```vibe
{#let learner='{"name":"Sam","level":"intermediate"}'}
{#let module='{"slug":"fractions"}'}

@include('lesson-header.vibe')
{=lessonBody -> body label="[[learner.name]]:[[module.slug]]"}
{=generateHints(body, learner.level) -> hints}

{$body}
{$hints}
```

Example runtime observer intent:

```php
$runtime->when('OnTraceRecorded', function ($meta) use ($timeline) {
    $timeline[] = [
        'phase' => $meta->phase,
        'tag' => $meta->tag,
        'message' => $meta->message,
        'durationMs' => $meta->durationMs,
    ];
});
```

### 5. Compliance-safe Document and Disclosure Rendering

Application:

- contracts
- disclosures
- regulated reports

Why the runtime lifecycle matters:

- missing clauses must block output
- imports and section output need audit trails
- final renders should carry structured validation evidence

Key runtime behaviors:

- `OnValidationStarted`
- `OnValidationFailed`
- `OnValidationCompleted`
- `OnRenderStarted`
- `OnRenderCompleted`

Acceptance story:

- As a compliance runtime, I want required disclosures and imported jurisdiction clauses to be validated before final render, so that missing sections stop the document rather than silently producing invalid output.

Example Vibe template:

```vibe
{#let jurisdiction="ny"}
@include('agreement-core.vibe')
@include('disclosures/[[jurisdiction|lower]].vibe')

{=validateAgreement(jurisdiction) -> validation silent=true}
{#if var=validation equals="ok"}
@include('signature-block.vibe')
{/if}
```

Example runtime observer intent:

```php
$runtime->when('OnValidationFailed', function ($meta) use ($errors) {
    $errors[] = [
        'tag' => $meta->tag,
        'message' => $meta->message,
        'scopeKeys' => $meta->scopeKeys,
    ];
});
```

## Phase 1 Acceptance Stories

If Phase 1 is implemented correctly, the following should be true.

### Story 1: Stable Runtime Vocabulary

- As a downstream runtime maintainer, I can subscribe to parser-specific event/state names without relying on generic `_before` and `_after` hooks alone.

Acceptance criteria:

- parser runtime event constants exist
- parser runtime state constants exist
- names are scoped to parsing/runtime concerns rather than generic application concerns

### Story 2: Structured Runtime Metadata

- As a downstream runtime maintainer, I receive structured runtime metadata for major lifecycle events, so I can inspect expression, assignment, element identity, and timing without parsing log strings.

Acceptance criteria:

- runtime metadata exposes run id, phase, tag, element identity, and timing
- runtime metadata can optionally carry assignment and include/import details
- metadata shape is additive and does not break current listeners

### Story 3: Non-breaking Observer Layer

- As an existing parser consumer, I can ignore the new runtime behavior layer and keep using current parser hooks without changing my templates or parser setup.

Acceptance criteria:

- current parser APIs continue to work unchanged
- `Dev::do` and `Dev::apply` remain available
- runtime behavior support is opt-in

### Story 4: Trace-ready Execution Surface

- As a runtime integrator, I can record ordered lifecycle entries for parse, execute, and render phases without modifying core parser logic.

Acceptance criteria:

- an observer or bridge can subscribe to lifecycle phases
- trace entries preserve ordering
- tracing can be disabled with negligible cost

### Story 5: Downstream-friendly Extension Point

- As a downstream library maintainer, I can build policy, auditing, and replay tooling on top of the parser runtime without carrying a private parser fork.

Acceptance criteria:

- runtime vocabulary is documented
- lifecycle phases map cleanly to current parser classes
- the phased rollout leaves room for direct emissions later without forcing them immediately

## Immediate Follow-Up Tasks

The next implementation work should be split into smaller issues:

1. Add parser runtime event/state constants.
2. Add a parser runtime metadata object and trace entry model.
3. Add an opt-in runtime observer/trace collector that mirrors current parser lifecycle into parser-specific behaviors.
4. Add focused tests proving trace ordering and metadata population.

## Recommendation

The parser should become more Behavior-native by layering a stable runtime contract around it, not by replacing the parser’s existing hook and dispatch model.

That preserves the strengths DevElation already has:

- flexible hooks
- lightweight parsing objects
- extensible registries
- downstream freedom

And it gives downstream interpreters what they actually need:

- stable lifecycle names
- structured runtime metadata
- replay/debug visibility
- additive adoption
