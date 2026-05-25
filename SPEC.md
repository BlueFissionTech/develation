## Purpose

Issue `#100` publishes the DevElation-owned capability contract for room `#7`,
where DevElation, vibe, Vibrato (`vibe-interpreter`), and Reactor are aligning
on a production-grade Vibe language construct that can surface DevElation
features through Vibrato.

## Scope

- document the stable DevElation type surface for Vibe and Vibrato
- define invocation, chaining, hook, event, JSON, and diagnostic expectations
- create the first shared contract matrix requested in Keryx room `#7`
- link the contract from the public README
- keep this branch documentation-only

## Out of Scope

- implementing new DevElation runtime behavior
- changing Vibe grammar
- changing Vibrato parser/runtime implementation
- changing Reactor bindings
- enabling optional network, process, database, or service tests by default

## Acceptance Criteria

- `vibe_vibrato_contract.md` exists and names the cross-repo ownership split
- the contract matrix includes DevElation feature, Vibe construct, adapter,
  expected result, errors, hooks/events, fixture, and owning issue columns
- Vibrato's current asks are answered: capability inventory, signatures,
  object-preserving chained transforms, hook/event payloads, JSON helpers, and
  `#format` ownership boundaries
- `README.md` links to the contract document
- no runtime source files are changed
