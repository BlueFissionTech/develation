## Purpose

This branch publishes a DevElation-owned capability surface for general PHP
consumers. The goal is to clarify the library's own public design principles
without making DevElation responsible for project-specific integration plans.

## Scope

- document the general primitive, helper, hook, behavior, data, service,
  parsing, system, and security surfaces DevElation owns
- define roadmap gates for accepting new public capabilities
- link the capability surface from the public README
- keep this branch documentation-only

## Out of Scope

- implementing new runtime behavior
- documenting project-specific integration syntax or execution plans
- publishing coordination, automation, or tool instructions
- enabling optional network, process, database, or service tests by default

## Acceptance Criteria

- `capability_surface.md` describes DevElation capabilities in general library
  terms
- committed documentation avoids project-specific coordination references
- `README.md` links to the capability surface
- no runtime source files are changed by this branch
