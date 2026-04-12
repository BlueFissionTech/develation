# Prototypes Substrate

DevElation's `src/Prototypes/` surface is a generic substrate for world-modeling,
stateful entities, conditions, collectives, and causal context. It is not a
reasoning engine. It exists so other Blue Fission libraries can share one set
of signatures, metadata contracts, hooks, and event surfaces.

## Design Intent

- Keep the substrate generic and useful on its own.
- Prefer traits and lightweight interfaces over inheritance-heavy design.
- Reuse DevElation patterns:
  - `Obj` field storage
  - `Arr`, `Str`, `Val` normalization
  - `DevElation::apply` and `DevElation::do`
  - optional behavior dispatch when the carrier already supports it
- Do not embed Automata-specific graph or simulation logic in this library.
- Do not lead with new JenSS grammar. Runtime-backed adapters should come first.

## Core Surface

### Traits

- `Proto`
- `Blueprint`
- `Artifact`
- `Entity`
- `Agent`
- `Position`
- `Domain`
- `Collective`
- `HasConditions`
- `IsCausal`
- `HasCollectives`

### Interfaces

- `Contracts\\Conditional`
- `Contracts\\Causal`
- `Contracts\\DomainAware`
- `Contracts\\CollectiveAware`

## Semantic Stack

- `Proto`: anything knowable, measurable, labelable, relatable, and inspectable.
- `Blueprint`: a semantic pattern or template for a kind of thing.
- `Artifact`: a blueprint-realized thing with substance or thingness.
- `Entity`: a reactionary artifact that can respond to conditions, state, and events.
- `Agent`: an entity with autonomy or external control.
- `Domain`: a world/context model for members, collectives, rules, and subdomains.
- `Collective`: a shared-fate grouping inside a domain such as a flock, fleet, crowd, or cloud.
- `Position`: orthogonal multi-dimensional location or frame context.

## Normalized Metadata

Prototype-bearing carriers should be able to expose, directly or through
`snapshot()`, these common fields:

- `id`
- `kind`
- `name`
- `labels`
- `traits`
- `state`
- `properties`
- `measures`
- `relations`
- `conditions`
- `causes`
- `effects`
- `confidence`
- `history`
- `summary`
- `domain`
- `position`
- `collectives`

Additional metadata may be present for blueprint, artifact, agent, domain, and
collective-specific concerns.

## Conditions

`HasConditions` is intentionally simple. It is meant to support:

- action guards
- state prerequisites
- migration dependencies
- domain constraints
- game rules
- simulation defaults
- causal candidate filtering

Condition records may be:

- callables
- plain arrays
- simple named references

The default implementation understands:

- `path`
- `expected`
- `operator`
- `resolver`
- `confidence`

Supported operators include:

- `requires`
- `equals`
- `not_equals`
- `gt`
- `gte`
- `lt`
- `lte`
- `in`
- `not_in`
- `truthy`
- `falsy`

## Causality

`IsCausal` stores and filters candidate causes/effects. It does not perform
advanced inference by itself. Its purpose is to create a stable structure for:

- causal records
- candidate prior conditions
- effect chains
- hooks for future graph reasoning
- simulation and state-engine integration

Automata can later consume these records through graph edges, decision scoring,
or simulation loops.

## Domain And Collective Registry

The preferred grouping mechanism is a domain-level registry, not value tags.

- `Domain` owns members, subdomains, rules, defaults, state, and collectives.
- `Collective` represents shared membership and shared destiny.
- `HasCollectives` lets any prototype-bearing object join or leave named
  collectives without depending on a specific simulation or graph engine.

Examples:

- sheep -> flock
- trucks -> fleet
- people -> crowd or mob
- droplets -> cloud
- bulls -> herd or stampede

Collectives may carry:

- members
- shared rules
- shared state
- shared destiny
- shared position or anchor
- shared conditions
- shared causal hints

## Forward Compatibility

### Automata

This substrate is intentionally compatible with future adaptation in:

- `Goal`
- `Simulation`
- `GameTheory`
- `Comprehension`
- `DecisionTree`
- `Memory`
- graph/path reasoning

Expected integration approach:

- Automata consumes normalized snapshots and relation/condition/causal records.
- Automata keeps all real scoring, search, pathing, and simulation logic.
- Automata may wrap or extend Develation substrate traits in richer classes.

### Jenss Interpreter

This substrate is intentionally compatible with runtime adapters and modules
such as:

- `intelligence.agent`
- `intelligence.entity`
- `intelligence.artifact`
- `intelligence.domain`
- `intelligence.collective`
- `intelligence.blueprint`
- `intelligence.position`

Expected integration approach:

- start with runtime factories/adapters
- preserve `make(...)`, `snapshot()`, and `explain()`
- avoid grammar-first expansion until the substrate proves stable

## Existing Develation Enhancement Opportunities

These traits should remain connected to the rest of the library:

- `Behavioral\\StateMachine`
  - state guards and transitions can reuse `HasConditions`
  - event/state histories can reuse `IsCausal`
- `Data\\Graph`
  - prototype/domain/collective relationships can project naturally into nodes and edges
- `Data\\Schema`
  - conditions and defaults can inform schema constraints and pre-validation
- `Data\\Storage`
  - prototype snapshots serialize cleanly for persistence and migration planning
- `Services`
  - domain defaults and shared rules can help scope applications/services

## Current Scope

This first substrate pass focuses on:

- stable trait and interface signatures
- normalized metadata
- hooks and optional behavior dispatch
- domain/collective registries
- condition and causal records

It deliberately does not attempt:

- theorem proving
- full causal inference
- graph search
- game simulation
- Automata-specific reasoning
