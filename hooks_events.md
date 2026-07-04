# DevElation Hooks, Filters, And Events

This document indexes the public extension points exposed by DevElation's
global hook/filter layer and behavior-driven event layer. It is intended for
consumers who need to attach policy, instrumentation, or integration logic
without reading every implementation file first.

## Coverage Snapshot

The hook/filter and behavior surfaces are broadly integrated in lifecycle-heavy
areas of the library:

- Global hooks and filters are present in CLI utilities, parsing, data helpers,
  graph helpers, storage helpers, security, system helpers, networking, and
  prototypes.
- Behavior dispatching is heavily used in behavioral traits, async utilities,
  connection classes, CLI utilities, services, data/storage classes, and
  primitives that expose observable mutation.
- Lightweight contracts, marker interfaces, simple value objects, and low-level
  behavior definitions do not all expose hooks directly. That is intentional:
  hooks should sit where values enter, leave, mutate, dispatch, or cross IO
  boundaries.

At the time this reference was added, the source tree contained hundreds of
hook/action and behavior-dispatch call sites. Coverage is strongest in parsing,
CLI, connections, security, prototypes, and shared behavioral primitives.

## Global Hook And Filter API

Global hooks and filters are managed by `BlueFission\DevElation`.

```php
use BlueFission\DevElation as Dev;

Dev::up();

Dev::filter('_out', fn ($value) => $value);
Dev::action('_after', function ($result, $object = null) {
    // observe completed lifecycle work
});
```

- `Dev::filter($name, callable $function, $priority = 10)` registers a value
  filter.
- `Dev::apply($name = null, $value = null)` applies filters and returns the
  filtered value.
- `Dev::action($name, callable $function, $priority = 10)` registers a
  side-effect action.
- `Dev::do($name = null, $args = [])` runs action callbacks.
- `Dev::listen($eventOrBehavior)`, `subscribe()`, and `trigger()` provide a
  lightweight global event surface when a full behavioral object is not needed.
- DevElation hooks are inactive until `Dev::up()` is called.

If `$name` is `null`, DevElation generates a hook name from the caller class and
method. Prefer explicit names for public extension points that consumers are
expected to rely on.

## Common Hook Names

These names are used across multiple areas and should be treated as stable
library conventions.

| Name | Type | Purpose | Common locations |
| --- | --- | --- | --- |
| `_in` | filter | Normalize or replace inbound method arguments before work begins. | primitives, CLI, parsing, data, security, system, connections |
| `_out` | filter | Normalize, replace, or observe returned values before they leave a method. | primitives, CLI, parsing, data, security, system, connections |
| `_before` | action | Observe a method or render lifecycle before work occurs. | CLI, parsing, schema, security, system |
| `_after` | action | Observe a method or render lifecycle after work completes. | CLI, parsing, schema, security, system |
| `(auto/generated)` | filter/action | Generated from the caller class and method when `null` is passed as the hook name. | `Val`, `Str`, `ValFactory`, storage, queues, memory helpers |

## Specialized Hook Names

| Name | Type | Purpose | Locations |
| --- | --- | --- | --- |
| `_attribute` | filter | Filter a single parsed element attribute value. | `src/Parsing/Element.php` |
| `_attributes` | filter | Filter parsed attribute arrays. | `src/Parsing/Block.php`, `src/Parsing/Element.php`, `src/Parsing/TagDefinition.php`, `src/Parsing/Registry/TagRegistry.php` |
| `_cast` | filter | Filter datatype cast resolution for parsed element values. | `src/Parsing/Element.php` |
| `_element` | filter | Filter a parsed block element before it is stored. | `src/Parsing/Block.php` |
| `_output` | filter | Filter block render output. | `src/Parsing/Block.php` |
| `_value` | filter | Filter parsed scalar value output. | `src/Parsing/Element.php` |
| `_node` | filter | Filter graph node creation/input. | `src/Data/Graph/Graph.php` |
| `_node_added` | action | Observe graph node additions. | `src/Data/Graph/Graph.php` |
| `_connect` | filter | Filter graph edge connection payloads. | `src/Data/Graph/Graph.php` |
| `_edge` | filter | Filter node edge attributes. | `src/Data/Graph/Node.php` |
| `_edge_added` | action | Observe graph edge additions. | `src/Data/Graph/Node.php`, `src/Data/Graph/Graph.php` |
| `_edge_removed` | action | Observe graph edge removals. | `src/Data/Graph/Node.php` |
| `_options` | filter | Filter command lookup options. | `src/System/CommandLocator.php` |
| `_found` | action | Observe a resolved system command. | `src/System/CommandLocator.php` |
| `_custom` | filter | Filter custom string mutation output. | `src/Str.php` |
| `_irregulars` | filter | Filter pluralization irregular word rules. | `src/Str.php` |
| `_identicals` | filter | Filter pluralization identical-singular/plural rules. | `src/Str.php` |
| `_pre` | filter | Filter a string before pluralization rules run. | `src/Str.php` |
| `_post` | filter | Filter a string after pluralization rules run. | `src/Str.php` |
| `_post` | action | Observe post-processing in application file handling. | `src/Services/Application.php` |
| `http_status_texts` | filter | Replace or extend HTTP status text mappings. | `src/Net/HTTP.php` |
| `parsing.block.before_element` | action | Observe each element before a block renders it. | `src/Parsing/Block.php` |
| `parsing.block.after_element` | action | Observe each element after a block renders it. | `src/Parsing/Block.php` |
| `parsing.element.interpolate_attribute` | filter | Filter interpolated parsed attribute strings. | `src/Parsing/Element.php` |
| `parsing.element.interpolate_attribute.action1` | action | Observe interpolated parsed attribute strings. | `src/Parsing/Element.php` |
| `prototypes.causal.infer.in` | filter | Filter causal inference input records. | `src/Prototypes/IsCausal.php` |
| `prototypes.conditions.evaluate.in` | filter | Filter condition evaluation input. | `src/Prototypes/HasConditions.php` |
| `prototypes.proto.snapshot` | filter | Filter prototype snapshot payloads. | `src/Prototypes/Proto.php` |
| dynamic prototype hooks | action | Prototype helpers can dispatch named hooks from prototype lifecycle data. | `src/Prototypes/Support/PrototypeTools.php` |

## Hooked Areas

| Area | Files to inspect first | Typical hooks |
| --- | --- | --- |
| Primitive values | `src/Val.php`, `src/Str.php`, `src/Arr.php`, `src/Num.php`, `src/Flag.php`, `src/Date.php`, `src/Func.php` | `_in`, `_out`, auto-generated hooks, mutation events |
| CLI | `src/Cli/Console.php`, `src/Cli/Args.php`, `src/Cli/Util/*` | `_in`, `_out`, `_before`, `_after`, `OnProcessed`, `OnSent`, `OnReceived`, `OnChange` |
| Connections | `src/Connections/*`, `src/Connections/Database/*` | `_in`, `_out`, connection states, send/receive events, action failures |
| Data and storage | `src/Data/*`, `src/Data/Storage/*`, `src/Data/Queues/*` | `_in`, `_out`, auto-generated hooks, graph hooks, storage behaviors |
| Parsing | `src/Parsing/*`, `src/Parsing/Elements/*`, `src/Parsing/Registry/*` | `_in`, `_out`, `_before`, `_after`, `_attributes`, named parsing hooks |
| Security | `src/Security/Hash.php` | `_in`, `_out`, `_before`, `_after`, `DoProcess`, `OnSuccess`, `OnFailure`, `OnProcessed` |
| System | `src/System/*` | `_in`, `_out`, `_options`, `_found` |
| Prototypes | `src/Prototypes/*` | prototype-specific filters, dynamic prototype hooks, property-change dispatch |

## Behavior Event API

Objects using `Dispatches` can register and dispatch behaviors.

```php
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;

$object->when(Event::CHANGE, function ($behavior, $meta = null) {
    // observe changes
});

$object->trigger(Event::CHANGE, new Meta(data: ['field' => 'name']));
```

Objects using `Behaves` gain Events, States, Actions, history, and state
management through `perform()`, `can()`, `is()`, and `halt()`.

```php
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Meta;

$object->perform(Action::PROCESS, new Meta(data: $payload));
```

Use `Meta` when handlers need structured context such as `data`, `info`,
`when`, or `src`.

## Base Events

`BlueFission\Behavioral\Behaviors\Event` defines the common event names below.
Classes that use `Behaves` register these base events during initialization.
Classes that only use `Dispatches` may register a narrower set.

| Constant | Behavior name | Common meaning |
| --- | --- | --- |
| `Event::LOAD` | `OnLoad` | Object or process load. |
| `Event::UNLOAD` | `OnUnload` | Object or process unload. |
| `Event::ACTIVATED` | `OnActivated` | Object activation completed. |
| `Event::CHANGE` | `OnChange` | Value, field, or state changed. |
| `Event::COMPLETE` | `OnComplete` | Process completed. |
| `Event::STARTED` | `OnStarted` | Process started. |
| `Event::SUCCESS` | `OnSuccess` | Process succeeded. |
| `Event::FAILURE` | `OnFailure` | Process failed. |
| `Event::MESSAGE` | `OnMessageUpdate` | Informational message update. |
| `Event::BLOCKED` | `OnBlocked` | Action could not proceed. |
| `Event::CONNECTED` | `OnConnected` | Connection completed. |
| `Event::DISCONNECTED` | `OnDisconnected` | Disconnection completed. |
| `Event::CLEAR_DATA` | `OnClearData` | Data clear requested or completed. |
| `Event::CREATED` | `OnCreated` | Create lifecycle completed. |
| `Event::READ` | `OnRead` | Read lifecycle completed. |
| `Event::UPDATED` | `OnUpdated` | Update lifecycle completed. |
| `Event::SAVED` | `OnSaved` | Save lifecycle completed. |
| `Event::DELETED` | `OnDeleted` | Delete lifecycle completed. |
| `Event::SENT` | `OnSent` | Data sent. |
| `Event::RECEIVED` | `OnReceived` | Data received. |
| `Event::STATE_CHANGED` | `OnStateChanged` | Persistent state changed. |
| `Event::AUTHENTICATED` | `OnAuthenticated` | Authentication succeeded. |
| `Event::AUTHENTICATION_FAILED` | `OnAuthenticationFailed` | Authentication failed. |
| `Event::SESSION_STARTED` | `OnSessionStarted` | Session start completed. |
| `Event::SESSION_ENDED` | `OnSessionEnded` | Session end completed. |
| `Event::ERROR` | `OnError` | Recoverable error occurred. |
| `Event::EXCEPTION` | `OnException` | Exception condition occurred. |
| `Event::CONFIGURED` | `OnConfigured` | Configuration completed. |
| `Event::INITIALIZED` | `OnInitialized` | Initialization completed. |
| `Event::FINALIZED` | `OnFinalized` | Finalization completed. |
| `Event::PROCESSED` | `OnProcessed` | Processing completed with output. |
| `Event::STOPPED` | `OnStopped` | Process stopped. |
| `Event::ACTION_PERFORMED` | `OnActionPerformed` | Action handler ran. |
| `Event::ACTION_FAILED` | `OnActionFailed` | Action handler failed. |
| `Event::ITEM_ADDED` | `OnItemAdded` | Collection or item addition occurred. |

## Base Actions

`BlueFission\Behavioral\Behaviors\Action` defines active intent names.

| Constant | Behavior name |
| --- | --- |
| `Action::ACTIVATE` | `DoActivate` |
| `Action::UPDATE` | `DoUpdate` |
| `Action::CREATE` | `DoCreate` |
| `Action::READ` | `DoRead` |
| `Action::DELETE` | `DoDelete` |
| `Action::SAVE` | `DoSave` |
| `Action::CLICK` | `DoClick` |
| `Action::HOVER` | `DoHover` |
| `Action::SCROLL` | `DoScroll` |
| `Action::INPUT` | `DoInput` |
| `Action::RUN` | `DoRun` |
| `Action::START` | `DoStart` |
| `Action::STOP` | `DoStop` |
| `Action::RESTART` | `DoRestart` |
| `Action::PAUSE` | `DoPause` |
| `Action::RESUME` | `DoResume` |
| `Action::CONNECT` | `DoConnect` |
| `Action::DISCONNECT` | `DoDisconnect` |
| `Action::SEND` | `DoSend` |
| `Action::RECEIVE` | `DoReceive` |
| `Action::SYNC` | `DoSync` |
| `Action::LOGIN` | `DoLogin` |
| `Action::LOGOUT` | `DoLogout` |
| `Action::AUTHENTICATE` | `DoAuthenticate` |
| `Action::AUTHORIZE` | `DoAuthorize` |
| `Action::THROW_ERROR` | `DoThrowError` |
| `Action::CATCH_ERROR` | `DoCatchError` |
| `Action::HANDLE_EXCEPTION` | `DoHandleException` |
| `Action::VALIDATE` | `DoValidate` |
| `Action::FILTER` | `DoFilter` |
| `Action::TRANSFORM` | `DoTransform` |
| `Action::PROCESS` | `DoProcess` |
| `Action::REFRESH` | `DoRefresh` |
| `Action::LOAD_MORE` | `DoLoadMore` |

## Base States

`BlueFission\Behavioral\Behaviors\State` defines persistent condition names.

| Constant | Behavior name |
| --- | --- |
| `State::DRAFT` | `IsDraft` |
| `State::DONE` | `IsDone` |
| `State::NORMAL` | `IsNormal` |
| `State::READONLY` | `IsReadonly` |
| `State::BUSY` | `IsBusy` |
| `State::IDLE` | `IsIdle` |
| `State::LOADING` | `IsLoading` |
| `State::SAVING` | `IsSaving` |
| `State::EDITING` | `IsEditing` |
| `State::VIEWING` | `IsViewing` |
| `State::PENDING` | `IsPending` |
| `State::APPROVED` | `IsApproved` |
| `State::REJECTED` | `IsRejected` |
| `State::FULFILLED` | `IsFulfilled` |
| `State::ARCHIVED` | `IsArchived` |
| `State::RUNNING` | `IsRunning` |
| `State::CHANGING` | `IsChanging` |
| `State::STATE_CHANGING` | `IsChangingState` |
| `State::CREATING` | `IsCreating` |
| `State::READING` | `IsReading` |
| `State::UPDATING` | `IsUpdating` |
| `State::DELETING` | `IsDeleting` |
| `State::AUTHENTICATING` | `IsAuthenticating` |
| `State::AUTHENTICATED` | `IsAuthenticated` |
| `State::UNAUTHENTICATED` | `IsUnauthenticated` |
| `State::AUTHORIZATION_GRANTED` | `IsAuthorizationGranted` |
| `State::AUTHORIZATION_DENIED` | `IsAuthorizationDenied` |
| `State::SESSION_STARTING` | `IsStartingSession` |
| `State::SESSION_ENDING` | `IsEndingSession` |
| `State::CONNECTING` | `IsConnecting` |
| `State::CONNECTED` | `IsConnected` |
| `State::DISCONNECTING` | `IsDisconnecting` |
| `State::DISCONNECTED` | `IsDisconnected` |
| `State::SYNCING` | `IsSyncing` |
| `State::SYNCED` | `IsSynced` |
| `State::OUT_OF_SYNC` | `IsOutOfSync` |
| `State::SENDING` | `IsSending` |
| `State::RECEIVING` | `IsReceiving` |
| `State::OPERATIONAL` | `IsOperational` |
| `State::NON_OPERATIONAL` | `IsNonOperational` |
| `State::MAINTENANCE` | `IsMaintenance` |
| `State::DEGRADED` | `IsDegraded` |
| `State::FAILURE` | `IsFailure` |
| `State::INTERACTING` | `IsInteracting` |
| `State::NON_INTERACTIVE` | `IsNonInteractive` |
| `State::CONFIGURING` | `IsConfiguring` |
| `State::INITIALIZING` | `IsInitializing` |
| `State::FINALIZING` | `IsFinalizing` |
| `State::PROCESSING` | `IsProcessing` |
| `State::STOPPED` | `IsStopped` |
| `State::WAITING_FOR_INPUT` | `IsWaitingForInput` |
| `State::PERFORMING_ACTION` | `IsPerformingAction` |
| `State::ACTION_COMPLETED` | `IsActionCompleted` |
| `State::ERROR_STATE` | `IsErrorState` |

## Event-Integrated Areas

| Area | Event behavior |
| --- | --- |
| Behavioral core | `Dispatches` handles `behavior()`, `when()`, `dispatch()`, and `trigger()`. `Behaves` adds base Events, States, Actions, history, and state transitions. |
| Primitives | `Arr`, `Date`, `Flag`, `Func`, `Val`, and related classes trigger change or exception events around mutable operations. |
| Async | `Async`, `Promise`, `Remote`, and socket helpers perform started, running, success, failure, error, processed, complete, stopped, finalized, and unload events. |
| Connections | `Connection`, `Curl`, `Socket`, `Stream`, `Stdio`, database links, and IO helpers perform connection, send, receive, processing, success, failure, and error behaviors. |
| CLI | `Console`, `Args`, prompts, status/progress/spinner/table/canvas helpers perform or trigger process, transform, send, receive, change, and processed behaviors. |
| Data and storage | Storage classes, data records, schema helpers, queues, files, directories, and graph helpers use hooks for data flow and behaviors for lifecycle outcomes where available. |
| Services | `Application`, `Service`, `Gateway`, response/request helpers, and authentication-related classes use behavior dispatch to route application events and service actions. |
| Prototypes | Prototype tools dispatch package-owned prototype events, including property-change and dynamic hook dispatch for domain object lifecycles. |
| Security | `Hash` performs process, success, failure, and processed events around hashing and file hashing. |

## Choosing Hooks Or Events

Use filters when a value should be transformed before it enters or leaves a
method. Use actions when code should observe or react to a lifecycle point
without changing the returned value. Use behavior events when the object itself
has meaningful state, action, history, or observers.

Prefer:

- `Dev::apply('_in', $value)` for policy-controlled input normalization.
- `Dev::apply('_out', $value)` for policy-controlled return values.
- `Dev::do('_before', [...])` and `Dev::do('_after', [...])` for general
  lifecycle observation.
- `$object->perform(Action::PROCESS, new Meta(data: $payload))` when an object
  is doing work and should track behavior.
- `$object->trigger(Event::CHANGE, new Meta(data: $change))` when mutation
  should be observable but does not need a full action/state transition.

Avoid adding hooks to every line. A hook should have a stable name, a stable
payload shape, and a clear reason a consumer would attach to it.

## Keeping This Reference Current

When adding a hook or behavior surface:

1. Add a stable name or use an existing convention.
2. Document the name, type, payload, and location in this file.
3. Add or update tests when behavior, payload shape, or return values matter.
4. Prefer general package-owned names over names tied to one consuming project.
