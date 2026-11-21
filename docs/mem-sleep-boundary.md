# Mem sleep integrity boundary

`Mem::sleep($id)` keeps the registered object in memory when no storage is
configured. Without a backing store, replacing the pool entry with a sleeping
marker would lose the only recoverable copy. `sleepAll()` follows the same
rule.

With a configured store, `Mem` serializes an object once before writing it.
`wakeup($id)` reads that serialized value, deletes the stored entry, and
restores the object to the pool. The provider-free regression uses only a
locally produced object and an in-memory test store; it does **not** qualify a
specific storage backend, persistent durability, write-failure recovery, or
concurrent access.

A store that throws during `write()` leaves the original object registered;
the exception remains visible. A backend that returns normally without
actually persisting cannot be distinguished through the current generic
`Storage::write()` return contract, so silent-write failure remains unqualified.

Storage supplied to `Mem` must be trusted: `wakeup()` currently calls PHP
`unserialize()` on the retrieved bytes. Do not use it with untrusted or
externally mutable state. A safe untrusted-state policy and a verified
write/retention outcome are separate contract and implementation work under
issue #342; this change must not be treated as approval of either.
