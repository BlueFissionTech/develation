# Ref Primitive

`BlueFission\Ref` is the DevElation primitive for reference-like values and
runtime handles. It follows the `Val` family style while keeping raw PHP handle
interop available through `val()` and `unwrap()`.

Use `Ref` when a reference, stream, pipe, or object handle benefits from:

- explicit caller-owned versus owned close policy;
- hookable read and write boundaries;
- lifecycle events for read, write, connect, and close;
- stream cursor helpers for seeking, rewinding, position checks, end-of-file
  checks, truncation, and chunked reads;
- fluent metadata for mode, target, status, or subsystem ownership.

Keep native PHP resource handling when wrapping would obscure a low-level API
contract or when a call site only touches a handle once.

`Ref::is($value)` is the primitive type predicate. It returns true for native
resources, readable/writable/closeable object handles, callable object handles,
or values explicitly bound through `Ref::bind()`. It does not treat scalar
paths, URLs, or arbitrary non-null values as references.

Use the constructors by intent:

- `Ref::make($value)` / `ref($value)` wraps an arbitrary value on the common
  `Val` family surface.
- `Ref::resource($handle, $options)` wraps an already-open resource or handle.
  It does not open anything and defaults to `owned => false`, so `close()` will
  not close caller-owned handles unless ownership is set explicitly.
- `Ref::open($target, ['mode' => 'r'])` opens a stream with `fopen()` and wraps
  it as owned. Prefer this when the `Ref` should manage the stream lifecycle;
  prefer `fopen()` plus `Ref::resource()` when existing PHP APIs own the handle.
- `Ref::bind($value)` binds to a PHP variable by reference.

```php
use BlueFission\Ref;

$handle = fopen('php://temp', 'r+');

$ref = Ref::resource($handle, ['owned' => true])
    ->write('payload');

rewind($handle);

$payload = $ref->read();
$ref->close();
```

Streams can be consumed incrementally:

```php
$ref = Ref::open($path);

foreach ($ref->chunks(8192) as $chunk) {
    // Process without loading the whole stream into memory.
}

$ref->rewind();
$position = $ref->tell();
$ref->seek(0)->truncate();
```

Reference binding is available when PHP can safely bind the value:

```php
$value = 'draft';
$ref = Ref::bind($value);

$ref->val('ready');

echo $value; // ready
```

## Migration Audit

Issue `#208` identifies the first migration candidates:

- `Connections\Stdio` stream reads and caller-owned handles;
- `Connections\Stream` internal stream handle management;
- `System\Process` process pipes and output/error streams;
- `System\System` background process stream reads;
- `Data\FileSystem` file handle lifecycle;
- `Data\Storage\Memory` stream usage;
- queue implementations that hold file or stream handles;
- hash/resource normalization paths;
- XML or parser utilities that read streams.

Migrate only when `Ref` improves ownership, behavior consistency, or
readability. Leave native resource calls in place where direct PHP semantics are
clearer.
