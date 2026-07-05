# DevElation Stable Roadmap

DevElation is the low-level PHP substrate for Blue Fission projects: primitive
value objects, resource handles, data/storage abstractions, behaviors, services,
connections, parsing, CLI, and package-owned helper conventions. Its direction
should stay broad enough for the Materia suite, Kyber suite, Opus and add-ons,
Automata, Reactor, Annex, JenSS, BlueCore, and microservice consumers without
turning DevElation into any one of those higher-level products.

## Release Baseline

- `v1.3.37-alpha` captured the pre-Ref master state.
- `v1.3.38-alpha` introduced the `Ref` primitive, ownership-aware handle
  helpers, conditional `Val` chain behavior, and scalar predicate naming cleanup.
- `v1.3.39` is the first stable release candidate line after production comment
  cleanup, Composer metadata refresh, and grouped PHPUnit validation.

Stable releases should preserve raw PHP interop where native contracts matter.
DevElation should wrap values and handles when lifecycle, hooks, metadata,
events, or reuse make that useful; it should not hide native PHP APIs merely for
surface consistency.

## Strategic Pillars

### 1. Primitive And Helper Consistency

The primitive family should remain coherent across `Val`, `Str`, `Arr`, `Num`,
`Flag`, `Date`, `Func`, `Ref`, `Obj`, and collection objects.

- Keep `make()` as the generic factory surface.
- Keep named constructors such as `Ref::resource()`, `Ref::open()`, and
  `Ref::bind()` for lifecycle-specific intent.
- Keep `Class::is($value)` predicates aligned with the primitive's actual type
  contract.
- Add global helpers only for object lifecycles, not one-off operations.
- Avoid helper names that collide with PHP built-ins or framework-level
  concepts DevElation does not own.

### 2. Resource, Process, And Stream Lifecycle

`Ref` is now the common language for references, stream resources, process
pipes, caller-owned handles, owned handles, read/write hooks, cursor movement,
truncation, and chunked reads.

Next steps:

- Continue migrating resource boundaries only where `Ref` clarifies ownership.
- Leave native `is_resource()`, `fread()`, `fwrite()`, `fseek()`, and related
  APIs inside low-level stream implementations where exact PHP semantics matter.
- Add examples for stream reads, process pipes, storage-backed streams, and hash
  normalization.
- Keep object-handle support conservative: readable, writable, closeable,
  callable, or explicitly bound values.

### 3. Data, Storage, And Service Contracts

Materia, Kyber, Opus, BlueCore, and microservice consumers need substitutable
data access and service primitives more than application-specific shortcuts.

- Keep `Storage`, SQL, SQLite, Mongo, session, cookie, memory, disk, queue,
  schema, and graph APIs consistent around activation, read/write/delete,
  status, data, and query inspection.
- Prefer storage injection over concrete datasource assumptions.
- Keep optional service tests opt-in so clean installs stay reliable.
- Document which constructors are side-effect-free and which operations connect,
  create, write, or delete.
- Treat `Services\Client` as a base HTTP-backed integration contract that
  downstream clients extend deliberately.

### 4. Application And Framework Interop

DevElation should support Opus, BlueCore, Reactor, and service add-ons without
becoming their framework layer.

- Keep routing, request, response, mapping, and service execution helpers
  package-owned and documented.
- Keep Reactor integration focused on predictable values, forms, templates, and
  request/response surfaces rather than frontend-specific policy.
- Let Opus and BlueCore own application assembly while DevElation owns the
  reusable primitives and contracts beneath them.
- Keep public docs free of local workflow, workstation, and harness details.

### 5. Intelligent And Automation Boundaries

Automata, JenSS, Annex, and intelligent microservices should compose DevElation
instead of being embedded into it.

- DevElation owns value normalization, dispatch, hooks, resources, storage,
  parsing, services, CLI, and system utilities.
- Automata owns AI and automation behavior.
- JenSS owns configurable intelligence and grammar/runtime configuration.
- Annex owns workflow/interoperability flows.
- DevElation examples may show composition points, but automation-specific
  orchestration belongs in those adjacent packages.

## Documentation Roadmap

The repository documentation and wiki should mirror the library surface:

- Philosophy and release policy.
- Primitive family and helper conventions.
- `Ref` lifecycle and resource ownership.
- Data, storage, queues, schema, and graph.
- Services, request/response, mappings, clients, and application helpers.
- Connections, process, system, CLI, and network utilities.
- Hooks, behaviors, events, states, actions, and metadata.
- Parsing, HTML, templates, and examples.
- Testing, optional integrations, and migration guidance.

The GitHub Pages site should stay concise and branded: package identity,
install command, release status, docs map, examples, and Blue Fission visual
identity. The provided DevElation circle logo should be used as the primary
brand asset once the site is added.

## Near-Term Milestones

1. Keep stable branch CI green across supported PHP versions.
2. Publish the refreshed wiki from the stable documentation set.
3. Add a small GitHub Pages site with Blue Fission branding and the DevElation
   circle logo.
4. Expand examples around `Ref`, storage substitution, request/service mapping,
   and primitive helper idioms.
5. Audit Materia, Kyber, Opus, add-ons, and microservice consumers for helper
   duplication that should move into DevElation.
6. Open package-specific follow-up issues for any downstream migration that
   would create breaking behavior if done inside DevElation directly.

## Acceptance Gates For New Public Surface

- The capability is useful to DevElation as a standalone PHP library.
- Method names, arguments, return values, and side effects are documented or
  tested.
- Static and instance behavior are consistent where both forms are supported.
- Optional integrations do not make default installs or test runs depend on
  external services.
- Native PHP interop remains available where consumer code or PHP extensions
  expect raw values or resources.
