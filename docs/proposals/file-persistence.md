# File persistence and byte-exact replacement proposal

**Status: proposal for #259; the APIs below are not implemented.** Do not use
this document as a migration instruction or as evidence of a released contract.

## Current boundaries

| Surface | Observed behavior | Contract consequence |
| --- | --- | --- |
| `File` / `doc()` | The no-argument constructor starts with no backend. `contents()` and `append()` change only the in-memory buffer. Before the unbound-operation guard, `write()` and `read()` called `method_exists()` on a null backend and raised `TypeError`. The follow-on guard reports `LogicException` instead. | A buffer-only file must fail deliberately when persistence is requested. A read-only `exists()` probe must remain usable without binding or creating anything. |
| `Directory` | Its constructor accepts `IData`. | This does not prove `IData` is sufficient for `File`: `File::read()` and `File::write()` also expect a backend with `NAME_FIELD` and `PATH_FIELD` static metadata. |
| `FileSystem` | Supports a configured root and explicit `mkdir()`. Its existing write path transforms existing text MIME content with `stripslashes()`. The first #259 slice covers empty-string and string-zero replacement, not arbitrary byte fidelity. | Legacy `write()` behavior must not change implicitly. Exact replacement needs a separate opt-in path and a verified root/target boundary. |
| `Disk` storage | Exposes the storage metadata used by `File`, but must be activated. It can JSON-encode empty content and delegates disk writes through `FileSystem`. | It is not currently a literal-byte backend merely because it implements `IData`. |

The original `File` no-backend reproduction on PHP 8.2 raised a `TypeError`
from `method_exists(null, 'write')`. The follow-on unbound-operation guard
changes only that failure mode; it does not select a public binding signature
based on the `Directory` constructor alone.

## Proposed acceptance contract

1. Preserve no-argument `File` construction, `doc()`, buffer `contents()` and
   buffer `append()`, and side-effect-free existence probes. An unbound
   `read()` or `write()` fails with a deliberate, documented diagnostic rather
   than a native type error or silent success.
2. Provide a public, typed way to bind a **compatible literal-document
   backend**. Define how name, directory, contents, activation, errors, and
   status flow through it. Do not advertise arbitrary `IData` binding until a
   test proves a non-`FileSystem` implementation satisfies that contract.
3. Provide an explicit opt-in byte-exact **replacement** operation. It must
   write the supplied bytes unchanged, including backslashes, JSON escapes,
   Unicode, newlines, NUL, empty string, and string zero. It must replace a
   longer existing file without leaving trailing bytes. Repeating the write
   must not append. Keep legacy `FileSystem::write()` transformation behavior
   unchanged unless a separate compatibility decision authorizes migration.
4. Keep buffer append distinct from on-disk append and replacement. If a disk
   append API is added, name it separately and specify whether it creates a
   missing file. Do not infer disk append from `File::append()`.
5. Do not create parent directories in read-only probes or by default in
   replacement. An explicit parent-creation option, if accepted, must verify
   that the resolved target remains within the configured root, reject escapes
   and symlink redirection, and report creation/write failures truthfully.
6. Define success from completed IO and byte comparison, not from a stale
   status field or an unbound object. Failed/missing/denied targets must not
   report success or overwrite unrelated files.

## Review decisions before implementation

- Which compatible backend contract should `File` expose: a dedicated
  literal-document interface, a constrained storage adapter, or a narrower
  `FileSystem` binding? A generic `IData` parameter alone is insufficient for
  the present metadata and activation assumptions.
- Should exact replacement be a separate `FileSystem` method, an explicit
  write mode, or a typed operation on the bound `File`? Pick one public shape
  after checking existing call sites; default legacy writes must remain stable.
- Should missing parents always fail unless an explicit option is set? The
  proposed default is **no creation**, subject to maintainer review.
- What status/exception convention should distinguish buffer-only misuse,
  invalid target, denied write, and partial write? Preserve the existing
  event/behavior vocabulary where it provides a truthful result.

## Required fixture matrix

For each accepted public entrypoint, test creation, repeated writes, shorter
replacement, empty replacement, string zero, backslashes, escaped JSON,
Unicode, newlines, NUL, and bounded binary bytes by reading raw bytes back.
Test paths containing spaces, existing and missing parents, denied targets,
root escapes and symlinks where supported. Verify read-only checks do not
create paths. Test legacy text-write transformation separately so opt-in byte
fidelity does not silently change the default.

Neither schema migration nor a consumer-specific adapter belongs in this
contract. Do not close #259 until the accepted implementation, compatibility
proof, and release requirements are met.
