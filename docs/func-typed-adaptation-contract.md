# Opt-in typed Func arguments (review proposal)

This is a contract proposal for issue #308, not a shipped API. Ordinary
`Func::call()` and `Func::__invoke()` must keep forwarding the caller's values
without primitive wrapping.

## Existing behavior and boundary

- `Func::expects()` exposes signature metadata or simplified reflection data.
  It reports a parameter name and type string, not enough to distinguish
  references, variadics, and every union alternative.
- `Func::call()` obtains arguments, builds reference slots from `expects()`,
  and uses `call_user_func_array()`. The wrapper already accepts closures,
  callable arrays, invokable objects, and static method callables.
- `signature()` records explicit metadata; it does not change the underlying
  callable's PHP-declared parameter types. String `body()` compilation is a
  separate trusted-input facility and is not an adaptation path.
- `Str::make()`, `Num::make()`, and `Arr::make()` are existing primitive factory
  surfaces. They must not be applied to every argument globally.

## Proposed opt-in entry point

`Func::callAdapted(...$args): mixed` would adapt only arguments whose *actual
reflected callable parameter* declares exactly one of `Str`, `Num`, or `Arr`
(or that one type plus `null`). It would then invoke the same wrapped callable
once. The ordinary `call()`, `__invoke()`, constructor, and `signature()` paths
remain unchanged. A callable array or invokable object already wrapped by
`Func` can use this entry point; no new general-purpose object method invoker
is proposed.

| Parameter/input | Proposed behavior |
| --- | --- |
| Already an instance of the declared primitive | Pass the same object through unchanged. |
| `Str` with a native string, `Num` with a native int/float, `Arr` with a native array | Wrap once using the corresponding primitive factory, then invoke. |
| Nullable primitive with `null` | Pass `null` unchanged. |
| Parameter with an omitted default | Leave it omitted; let PHP apply the declared default. |
| Variadic primitive parameter | Adapt each supplied positional or named variadic value independently; preserve key/order semantics. |
| Plain native type, interface, unrelated class, mixed, or multi-type union | Do not adapt; pass to PHP's normal type check. |
| Primitive parameter declared by reference | Do not replace an aliased caller value. If it needs conversion, reject before invoking; an already-typed object remains by-reference. |
| Unsupported native value or a failed primitive conversion | Fail before invoking, with a type/conversion exception that identifies the parameter but does not include sensitive argument contents. |

Named arguments should be matched by reflected name and forwarded with their
original names; unknown names, duplicates, missing required arguments, and
positional-after-named cases retain PHP's native errors. `signature()` metadata
must not override PHP reflection for adaptation or silently change the
callable's declared type. An untyped parameter is never wrapped.

The accepted native inputs above are intentionally narrow. Whether factory
conversions with more liberal inputs (for example numeric strings) should be
supported is a review question, not an implicit promise. A union that contains
both a primitive and another non-null type is ambiguous and is left unchanged
in this proposal.

## Test and compatibility plan

- Closure and callable-array/invokable-object cases for each primitive, with
  exact object identity for already-typed values and a single callback call.
- Nullable and omitted-default cases; positional, named, and variadic inputs
  without argument reordering or duplicate invocation.
- A by-reference primitive parameter: already-typed object preserves the
  reference; scalar conversion fails without changing caller state or running
  the callback.
- Multi-type union, unrelated native/class type, unknown named argument, and
  unsupported conversion: assert explicit failure and no side effect.
- Existing `Func::call()` and `__invoke()` tests remain unchanged, including
  current argument forwarding and return values.
- Compare reflection and wrapping overhead on repeated small callbacks before
  recommending this path for hot loops. Do not introduce a global conversion
  flag or cache mutable argument data across calls.

Maintainers and affected callers must review the public entry point, exact
factory input policy, named/variadic/reference behavior, error type, and
performance tradeoff before implementation. This remains distinct from the
internal-usage audit in #302 and does not complete issue #308.
