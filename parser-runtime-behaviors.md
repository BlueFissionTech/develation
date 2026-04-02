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
