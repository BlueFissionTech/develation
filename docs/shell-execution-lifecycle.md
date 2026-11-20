# Shell execution and settlement (review plan)

This is a review plan for issue #275, not a fix or a public command runner.
`Async\Shell` is a local queued-process wrapper. It must not acquire shell
parsing, deployment, privilege, or orchestration policy.

## Source-backed defect and existing boundary

- `Shell::do()` passes a closure containing `yield` to `Async::exec()`. PHP
  returns a `Generator` when that closure is invoked, without running its
  body. `Promise::try()` invokes the closure but neither iterates nor returns
  the generator. `System\Process::start()` therefore does not run.
- `Async::run()` is the explicit queue drain. Its promise wrapper currently
  sees `Promise::try()` return `null`; that is not evidence of child success.
- The current `ShellTest` accepts either `null` or the expected stdout after
  `Shell::run()`. It cannot distinguish a real command from the defect.
- An isolated `PHP_BINARY -r` marker probe on the current baseline left the
  marker empty both before and after `Shell::run()`, with neither fulfillment
  nor rejection callback invoked. This confirms the missing execution path
  without depending on a service or a platform-specific command utility.
- `System\Process::output()` exposes stdout, `status()` normally exposes a
  running boolean, and `close()` preserves an observed terminal exit code.
  There is no reviewed separate bounded stderr/result surface yet; #263 and
  draft PR #366 track that dependency. `Shell::do()` also documents a `Shell`
  return although it actually returns a `Promise`.

## Proposed behavioral contract for review

1. Queueing via `Shell::do($command, $priority)` must perform **no child
   execution**. Only `Shell::run()` drains and starts the child. Keep an array
   command path for shell-free argument passing; do not parse strings into
   argv or silently change legacy string-command interpretation.
2. A queued command settles its promise exactly once after a **verified**
   process outcome. Exit code zero may fulfill. A nonzero exit, start failure,
   or unconfirmed termination must not fulfill merely because stdout is empty
   or a generator yielded `null`.
3. Preserve stdout and stderr separately if the reviewed #263 result permits
   it. Define the fulfillment value and rejection reason only after the #263
   result shape and `Promise` callback compatibility are reviewed. Do not
   overload a plain output string with failure information.
4. Close only owned child pipes/resources on all completion and exception
   paths. Do not report timeout or cancellation semantics until #263 proves
   bounded waiting and termination on supported platforms. Do not claim child
   process-tree control.
5. Keep `Async::run()` as the explicit drain contract. Any fix to generator
   iteration must be local enough not to alter unrelated `Async`, `Remote`, or
   queue-adapter semantics; #276 reviews queue compatibility separately.

## Provider-free regression matrix for the implementation PR

Use `[PHP_BINARY, '-r', $code]`, never a production command. A temporary
marker file can show that `do()` alone has no side effect and `run()` executes
the child once; remove that test-owned marker in `finally`.

| Fixture | Assertion |
| --- | --- |
| Child writes marker and fixed stdout, exit 0 | Marker absent before drain, present once after; promise fulfills once with exact result. |
| Child writes fixed stderr, exit 7 | Rejection once, actual exit code and stderr preserved; no success callback. |
| Synthetic start failure | Rejection once, no guessed exit code, owned resources cleaned. |
| Two queued children | Drain order follows existing queue contract; no duplicate child starts or callbacks. |
| Exception during start/read/close | Original failure remains inspectable; cleanup attempted without masking it. |

The fixture needs a finite test watchdog so a regression cannot hang CI. That
watchdog does **not** imply the public Shell API has a timeout. Exact callback
value, stderr and cleanup tests depend on the reviewed #263 Process contract.
The existing permissive `ShellTest` should be replaced, not counted as proof.

Maintainer and affected-owner review is required for output/settlement shape,
cross-platform execution, and cleanup before implementation or landing. This
document does not complete issue #275.
