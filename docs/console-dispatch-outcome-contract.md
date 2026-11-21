# Console dispatch outcome: review contract

This is a source-backed design slice for issue #332, not an implemented API.
The next change should remain additive and keep `Application::run()` fluent.

## Current boundary

| Source | Observed behavior | Compatibility implication |
| --- | --- | --- |
| `src/Services/Application.php` `run()` | The service-dispatch branch calls private `message()` and discards its return, then returns the application. | A handler's return is not an observable run outcome. Do not change `run()` to return the payload. |
| `src/Services/Application.php` `message()` | Selects an application, service, behavioral, dispatcher, or callable recipient. It does not normalize the branches into one result. | A new outcome needs an explicit handled/missing distinction; `null` may be a valid handler payload. |
| `src/Services/Service.php` `message()` and `response()` | A callable result can be stored in `_response`; `response()` is typed `?string`. | This is not a general mixed payload or process-status contract. Do not silently repurpose it. |
| `src/Services/Application.php` `gatewayOutcome()` | Exposes a gateway halt, separate from console dispatch. | Keep gateway decisions distinct from service-handler results. |
| `tests/Services/ApplicationTest.php` | Covers positional console arguments and gateway outcomes, but not handler payload, missing handler, failure, or repeated-run outcome reset. | Add focused coverage before changing dispatch behavior. |

## Proposed invariants for review

1. Keep `run()` returning the same `Application` instance. A handler executes at most once per invocation; observing the result must not execute it again.
2. An opt-in read-only observation surface should distinguish **not dispatched**, **handled with a `null` payload**, **handled with another payload**, and **dispatch failure**. An integer payload, including a nonzero one, is not automatically a process exit code or failure.
3. Clear the prior console outcome when a new invocation begins. A gateway halt, HTTP route, and static-file branch must not expose a stale console outcome.
4. Do not swallow a handler exception or erase partial effects merely to populate an outcome. Decide whether a failed invocation stores an outcome before rethrowing, and specify what can be observed afterward.
5. Preserve HTTP route printing, gateway response, existing service callbacks/events, and `Service::response()` semantics. Keep host exit-code mapping outside this library.

An immutable outcome object and a nullable `Application` accessor are candidates, **not approved names or signatures**. The maintainer and affected owners must decide whether failure details are exposed, how missing services/behaviors are classified, and which recipient branches qualify as console dispatch before implementation.

## Focused acceptance fixtures

- A console handler returning a scalar, a nonzero integer, and `null`; each is invoked once, and `run()` remains fluent.
- A missing service and a missing behavior, independently; neither can be mistaken for a successful `null` payload.
- A throwing handler with an observable side effect before the throw; preserve the original exception and define the post-throw outcome.
- Two successive runs on one application, including success then missing/failure; no stale result.
- Existing HTTP route output and gateway-halt behavior unchanged.

Tests should use synthetic in-process handlers and no external service. After a contract decision, run the focused Services tests and the full PHP 8.2+ suite. This document authorizes no API, dependency, release, or landing change.
