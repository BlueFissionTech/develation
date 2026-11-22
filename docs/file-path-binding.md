# Path-bound File bytes (review candidate for #259)

`File::atPath($absolutePath, $optionalRoot)` creates an explicitly path-bound
`File`. Construction and `exists()` do not create a file. `contents($bytes)`
sets the in-memory buffer; `append($bytes)` appends only to that buffer.
`write()` on a path-bound File replaces the target with those exact bytes;
`read()` loads the exact bytes into the buffer. A failed read preserves the
previous buffer. An unset buffer cannot be written.

For one-off replacement, `FileSystem::replaceBytesAtPath($absolutePath,
$bytes, $optionalRoot)` has the same literal-byte behavior. It does **not**
invoke the text filters in legacy `FileSystem::write()`. Use
`FileSystem::readBytesAtPath()` for a corresponding raw read. The existing
no-argument `File` remains buffer-only and its unbound `read()`/`write()`
continue to fail deliberately. Legacy `FileSystem::write()` is unchanged.

Both byte operations require a local absolute path, an existing parent, and
a regular, non-symlink target if one already exists. When a root is supplied,
the resolved parent must be inside that root. No parent directory is created.
Invalid paths/root escapes, missing parents/files, nonregular targets, and IO
failures raise exceptions; replacement does not report partial writes as
success. This API does not promise atomic replacement, crash durability,
protection from concurrent path changes, or recursive descendant handling.

This is a proposed contract pending review and compatibility qualification,
not a released migration instruction.
