# IPC storage boundary

`BlueFission\IPC\IPC` is a channel-list convenience wrapper around an injected
`Storage`. It is not a durable queue or a process-safe transport.

`Storage::read()` returns the storage object; IPC reads its `contents()` as the
channel map. Missing/empty contents are an empty map. Existing channel values
must be arrays; malformed contents fail rather than being overwritten. `read()`
does **not** consume messages. `clear($channel)` removes that channel while
retaining other channels. The wrapper deactivates the storage after operations,
including exceptions after a successful connection.

The read/append/write sequence is not atomic. No lock, acknowledgment, replay,
deduplication, ordering across writers, or persistence confirmation is supplied
by IPC. An injected backend may have different durability and error reporting
semantics. Use a separately reviewed transport contract for concurrent writers
or service-host delivery guarantees. This repair does not qualify a particular
backend or change the `IIPC` public signatures.
