# Bounded process result contract (review proposal)

This is a design and test plan for issue #263, not a shipped execution API.
The existing `System\Process` methods remain unchanged in this slice. A child
process is an untrusted local program, not a deployment or privilege boundary.

## Current source and test evidence

- `Process::start()` returns the same object. It triggers an error event when
  `proc_open()` returns false, but offers no result value distinguishing that
  failure from an exited child. `ProcessTest` verifies that its generic error
  message does not disclose a synthetic secret command argument.
- `status()` normally returns a running boolean and caches an observed
  nonnegative terminal exit code. `close()` reuses that cached code if
  `proc_close()` no longer supplies one. Existing tests cover exits 0 and 7
  after polling and stdout reading; this preservation must not regress.
- `output()` reads stdout. There is no stable public separate stderr accessor.
  Default pipes and optional Windows file-backed capture have different read
  and cleanup paths. `stop()` closes pipes and the process resource but does
  not provide a distinct termination outcome.
- `Async\Shell` currently depends on these methods and has its own generator
  execution defect tracked in #275. A Shell fix must not infer success from
  empty stdout or claim a bounded result before this contract is reviewed.

## Proposed additive shape for review

An opt-in bounded call on a `Process` instance would return one immutable
result value rather than overload `status()`, `output()`, or `close()`. The
candidate operation is `runBounded(int $timeoutMs, int $maxOutputBytes)`.
Both arguments must be positive and finite; no implicit unlimited mode.

The proposed result has distinct fields:

| Field | Meaning |
| --- | --- |
| `outcome` | `exited`, `start_failed`, `timed_out`, `output_limit`, or `termination_unconfirmed` |
| `exitCode` | Integer only when actually observed; otherwise `null`, never guessed as zero |
| `stdout`, `stderr` | Separate byte strings; each may be partial on failure or limit |
| `stdoutTruncated`, `stderrTruncated` | Whether a stream exceeded its documented capture bound |
| `terminationRequested`, `terminationConfirmed` | Separate facts, not aliases for the timeout or exit code |

For a normal exit, `outcome=exited` even when the code is nonzero; callers
interpret the code explicitly. A start failure has no exit code. Hitting a
deadline or output bound requests termination, but must not be reported as
confirmed until observed. If the runtime cannot prove that the child stopped
within the supported platform contract, return an explicit unconfirmed
outcome, not success. A returned result does not imply child-tree isolation.

The capture limit applies to combined stdout and stderr bytes, with a
documented per-stream truncation flag. Both streams need concurrent draining
or another proven deadlock-free strategy; reading one stream to completion
before the other is not acceptable. Output remains raw, untrusted bytes;
neither logs nor events should echo command arguments or child output by
default. Redaction is caller policy, not an automatic promise.

## Lifecycle and platform proof required before implementation

1. Define how the default pipe mode and Windows file-backed mode both meet
   the same bounded capture semantics without losing final bytes. Current
   `output()` does not establish that proof.
2. Choose a monotonic deadline and bounded wait/poll interval. Distinguish
   requesting termination from observing exit. Document what cannot be
   guaranteed on PHP 8.2+ without an OS-specific process utility, including
   descendant processes and an uninterruptible child.
3. Specify ownership and cleanup of all pipes, process resources, and any
   temporary capture files on normal exit, start failure, timeout, output
   limit, and exceptions. Repeated result inspection must not rerun a command.
4. Confirm compatibility with existing `start()`, `status()`, `output()`,
   `stop()`, and `close()` behavior, including repeated `close()` after exit.
   Decide whether a bounded call may operate on an already-started instance.

## Proposed provider-free test matrix

Use `PHP_BINARY` with an argument array and deterministic `-r` fixtures; do
not run external services or production commands.

- stdout-only exit 0, stderr-only exit 7, and both streams at once; assert
  exact separate bytes, actual exit code, and no duplicate bytes.
- Synthetic `openProcess()` failure; assert `start_failed`, null exit code,
  no command argument in logs or error metadata, and resource cleanup.
- Sleeping child past a short deadline; assert requested/confirmed termination
  independently. Do not pass the test merely because a timer elapsed.
- Child writing beyond the combined cap; assert bounded memory, partial bytes,
  truncation flags, and termination outcome.
- Windows capture and ordinary pipes; assert final reads, cleanup of owned
  temporary files, and no leaked handles after repeated inspection.
- Preserve the existing exit 0/7 polling-and-close tests as regression gates.

Maintainer review is required for the public signature, result representation,
timeout and termination guarantees, output policy, and supported-platform
evidence. The same review should inform #275 before Shell advertises a
successful command result. This document does not complete issue #263.
