# Behavior Lifecycle Patterns for Staged Execution

This document describes how DevElation projects should model staged execution flows with `Event`, `State`, `Action`, and `Meta`. It applies to parser-like, renderer-like, importer-like, queue-like, and other multi-phase runtimes without making those runtimes part of DevElation itself.

The goal is a stable, traceable pattern:

- use `State` while a phase is active
- use `Action` for work the object is asked to perform
- use `Event` when a phase, action, or transition has happened
- carry structured context in `Meta`
- keep application-specific lifecycle names outside DevElation's core behavior catalog unless they become broadly useful

## Existing Primitives

DevElation already exposes the building blocks needed for lifecycle work:

- `BlueFission\Behavioral\Behaves` for objects that register and perform behaviors
- `BlueFission\Behavioral\Dispatches` for event dispatch and handler registration
- `BlueFission\Behavioral\Behaviors\Event` for momentary notifications
- `BlueFission\Behavioral\Behaviors\State` for persistent runtime conditions
- `BlueFission\Behavioral\Behaviors\Action` for executable commands
- `BlueFission\Behavioral\Behaviors\Meta` for behavior context
- `Dev::do(...)` and `Dev::apply(...)` hooks for helper-level interception where a full behavioral object is unnecessary

These pieces should be composed before adding a new abstraction. A specialized lifecycle wrapper is appropriate only when it removes repeated coordination logic or captures a reusable DevElation-level concept.

## Recommended Lifecycle Phases

The exact phases depend on the object, but staged execution should map to the following vocabulary where possible.

| Phase | Meaning | Recommended state | Recommended action | Recommended events |
| --- | --- | --- | --- | --- |
| Bootstrap | Configure dependencies, inputs, defaults, and run context. | `State::INITIALIZING`, `State::CONFIGURING` | `Action::START`, `Action::ACTIVATE` | `Event::INITIALIZED`, `Event::CONFIGURED`, `Event::STARTED` |
| Discover | Find items, blocks, records, files, or units of work. | `State::PROCESSING`, `State::READING` | `Action::READ`, `Action::PROCESS` | `Event::ITEM_ADDED`, `Event::READ`, `Event::PROCESSED` |
| Prepare | Normalize data, bind context, filter unsupported work, and prepare execution inputs. | `State::PROCESSING`, `State::CHANGING` | `Action::FILTER`, `Action::TRANSFORM`, `Action::VALIDATE` | `Event::CHANGE`, `Event::PROCESSED`, `Event::FAILURE` |
| Execute | Run the main operation, invoke helpers, write output, or perform a command. | `State::PERFORMING_ACTION`, `State::RUNNING` | `Action::RUN`, `Action::PROCESS`, `Action::SAVE` | `Event::ACTION_PERFORMED`, `Event::ACTION_FAILED`, `Event::SUCCESS`, `Event::FAILURE` |
| Complete | Mark the operation finished and expose output/status. | `State::DONE`, `State::IDLE` | `Action::STOP` | `Event::COMPLETE`, `Event::STOPPED`, `Event::MESSAGE` |
| Trace | Record ordered, redactable execution evidence for diagnostics or replay. | `State::PROCESSING` or a domain-specific trace state | usually none, or `Action::PROCESS` | `Event::MESSAGE`, `Event::STATE_CHANGED`, domain-specific trace events |

Use the generic constants when their meaning is clear. If a domain needs more precision, register domain-specific behavior names on the owning object instead of expanding DevElation's global catalog prematurely.

## Metadata Envelope

`Meta` is the standard carrier for lifecycle context. Keep the top-level `Meta` fields aligned with their existing purpose:

- `when`: the behavior, phase, or action the metadata describes
- `info`: a short status or diagnostic message
- `data`: structured context for the lifecycle step
- `src`: the object or source that emitted the behavior

For staged execution, prefer an associative `data` payload with stable, dotted keys:

```php
new Meta(
    when: Action::PROCESS,
    info: 'Processing source input',
    data: [
        'run.id' => $runId,
        'phase' => 'execute',
        'step' => 'process',
        'source.type' => 'template',
        'input.count' => 3,
        'output.count' => 2,
        'success' => true,
        'duration.ms' => 12,
    ],
    src: $this
);
```

Recommended `data` keys:

- `run.id`: stable id for a single execution pass
- `phase`: lifecycle phase such as `bootstrap`, `discover`, `prepare`, `execute`, `complete`, or `trace`
- `step`: a lower-level step name within the phase
- `source.type`: general source family, such as `file`, `template`, `record`, or `payload`
- `source.path`: source identifier when safe to expose
- `input.count`: summarized input size
- `output.count`: summarized output size
- `target`: assignment, destination, or output target
- `success`: boolean success indicator
- `message`: machine-readable status detail when `info` is not enough
- `duration.ms`: elapsed time for the phase or step
- `error.class`: exception class or error family
- `error.message`: safe error summary

Avoid storing full object graphs, secrets, credentials, or complete application state in `Meta::data`. For traces, store summaries and stable identifiers.

## Hook And Trace Guidance

Behavioral objects should expose lifecycle activity through `when(...)`, `perform(...)`, `dispatch(...)`, and `halt(...)`. Helper-style functions can continue to use `Dev::do(...)` and `Dev::apply(...)` when the behavior would be too small to justify a full object.

Trace collectors should be opt-in:

- register handlers with `when(...)`
- append trace entries in the order handlers are called
- store behavior name, phase, run id, safe summaries, and result status
- redact sensitive values before they enter the trace
- keep replay input separate from trace output
- disable tracing without changing normal execution behavior

Replay should be treated as a consumer-owned capability unless DevElation provides a dedicated replay class. DevElation's responsibility is to make behavior names and metadata stable enough for a consumer to build replay safely.

## Library Versus Application Responsibility

DevElation should define broad behavior primitives and helper patterns. Applications and higher-level packages should define their own domain-specific lifecycle names when the generic behavior catalog is not precise enough.

DevElation-owned guidance:

- how to use `Event`, `State`, `Action`, and `Meta`
- which existing constants match common lifecycle phases
- how to shape metadata for traceability
- how to keep hooks additive and optional

Application-owned guidance:

- domain-specific phase names
- persistence format for traces
- replay policy
- user-facing diagnostics
- security and retention rules for execution logs

Do not name a behavior after an implementation detail that may change. Prefer conceptual names such as `OnProcessed`, `IsProcessing`, and `DoValidate` over class-specific names.

## Example Lifecycle Sequence

```php
use BlueFission\Behavioral\Behaves;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\Behaviors\State;

class StagedRunner implements IDispatcher
{
    use Behaves;

    public function run(array $items, string $runId): void
    {
        $this->perform(State::INITIALIZING, new Meta(
            when: Action::START,
            info: 'Starting staged run',
            data: ['run.id' => $runId, 'phase' => 'bootstrap']
        ));

        $this->perform(Event::INITIALIZED, new Meta(
            data: ['run.id' => $runId, 'phase' => 'bootstrap', 'success' => true]
        ));

        $this->perform(State::PROCESSING, new Meta(
            when: Action::PROCESS,
            info: 'Processing items',
            data: ['run.id' => $runId, 'phase' => 'execute', 'input.count' => count($items)]
        ));

        $this->perform(Action::PROCESS, new Meta(
            data: ['run.id' => $runId, 'phase' => 'execute', 'step' => 'process']
        ));

        $this->perform(Event::PROCESSED, new Meta(
            data: ['run.id' => $runId, 'phase' => 'execute', 'output.count' => count($items)]
        ));

        $this->perform(Event::COMPLETE, new Meta(
            data: ['run.id' => $runId, 'phase' => 'complete', 'success' => true]
        ));
    }
}
```

The companion test `BehaviorLifecyclePatternTest` verifies this sequence with an ordered trace and confirms that transient states are halted by the existing behavior handlers.

## Checklist

Use this checklist before introducing or documenting a staged behavior flow:

- Each phase has a clear `State`, `Action`, or `Event`.
- Transient states are halted after their completion event.
- `Meta::data` contains stable summaries, not full mutable runtime state.
- Trace collection is optional and append-only.
- Replay assumptions are documented separately from normal execution.
- DevElation-level names stay broad and reusable.
- Application-specific behavior names stay in the consuming package or application.
