# Process output and result contract (review proposal)

This scopes issue #287's output slice of #263. It does not adopt a new public
result type, start commands, or claim bounded execution on every platform.
Keep start failure (#288) and timeout/resource cleanup (#289) as separate
fixtures and review decisions.

## Current source boundary

- `Process::output()` reads the stdout pipe, or the opt-in file-backed stdout
  capture in Windows-safe mode. It does not expose a separate public stderr
  value. `status()` ordinarily returns whether the child is running; only a
  failed status call may return stderr content. Empty stdout is not a proof
  that execution succeeded.
- `status()` caches a known nonnegative terminal exit code. `close()` reuses
  that code if `proc_close()` later reports unavailable status. Existing tests
  prove exit 0 and exit 7 after repeated polling and output reading; preserve
  those exact outcomes.
- Current `output()` does not enforce an explicit retained-byte limit or
  define incremental versus final reads. Opt-in capture files are removed on
  `stop()` or `close()`. These source observations do not prove a deadlock,
  leak, or platform-wide process-tree semantics.

## Decisions before implementation

1. **Additive result.** Agree on a stable per-run result/read surface that
   distinguishes stdout, stderr, known exit code, running state, and
   unavailable observations. Keep `output()`, `status()`, and `close()`
   compatible or provide a reviewed migration. Never encode an unavailable
   exit as zero or infer success from empty text.
2. **Read timing.** Specify whether stream reads are incremental, final,
   consumptive, replayable, or snapshots. Define behavior after polling,
   early close, repeated reads, and reuse of the same `Process` instance.
   Child output is untrusted and may contain arbitrary bytes; no automatic
   redaction or safe logging promise follows from capture.
3. **Bounds and backpressure.** Set explicit per-stream and aggregate retained
   limits, overflow signal, and pipe-draining strategy. A bounded in-memory
   result must not simply stop reading one pipe while the child blocks on a
   full stdout or stderr buffer. Define what is and is not guaranteed on
   supported PHP 8.2+ platforms, including file-backed capture.
4. **Exit/status relationship.** Preserve exact known terminal codes after
   any sequence of polling and reading. Clarify what a result says when
   `proc_open` failed or a terminal code is unavailable; coordinate this
   with #288 and #289 without inventing a command-not-found guarantee where
   a platform only reports a child exit.

## Acceptance fixtures after contract review

Use `PHP_BINARY` with synthetic arguments, no shell-specific utilities,
network, or credentials. Exercise stdout only, stderr only, both streams,
empty streams, exit 0 and 7, repeated status/read/close, deliberately
over-limit output, and an unavailable result path. Assert byte counts and
overflow outcomes, exact known exit codes, no duplicate output, and no
unbounded retention. Run platform-specific capture fixtures where the
runtime actually supports them; a synthetic platform override is not native
Windows qualification. Keep optional child-service tests opt-in.

Maintainer and affected-owner review must settle the result shape, memory
limit, backpressure, lifecycle and compatibility before a source change or
landing. This proposal does not complete #287 or authorize deployment or a
release.
