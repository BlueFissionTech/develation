# Ref Primitive

`BlueFission\Ref` is the DevElation primitive for reference-like values and
runtime handles. It follows the `Val` family style while keeping raw PHP handle
interop available through `val()` and `unwrap()`.

Use `Ref` when a reference, stream, pipe, or object handle benefits from:

- explicit caller-owned versus owned close policy;
- hookable read and write boundaries;
- lifecycle events for read, write, connect, and close;
- fluent metadata for mode, target, status, or subsystem ownership.

Keep native PHP resource handling when wrapping would obscure a low-level API
contract or when a call site only touches a handle once.

```php
use BlueFission\Ref;

$handle = fopen('php://temp', 'r+');

$ref = Ref::resource($handle, ['owned' => true])
    ->write('payload');

rewind($handle);

$payload = $ref->read();
$ref->close();
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
