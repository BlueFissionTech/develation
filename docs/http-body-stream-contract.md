# HTTP body and stream contract proposal (#316)

**Status: characterization and review proposal, not an adopted adapter contract.**
This note does not change `HTTPClient`, `Request`, `Response`, or `Curl` behavior.

## Reproduced local gaps

`Request` accepts a missing body but declares `getBody(): StreamInterface`; calling
that method currently throws a `TypeError`. `Response` accepts raw response bytes
from `HTTPClient`, then likewise throws on `getBody()` because those bytes are not
a `StreamInterface`. `HTTPClient::requestBodyPayload()` decodes JSON text into an
array, so whitespace and byte spelling are lost before the transport receives
the payload. These cases are covered by provider-free characterization tests in
`HTTPBodyStreamContractTest.php`; they do not contact a network service.

## Proposed acceptance for a subsequent implementation

- A request with no explicit body yields a readable, empty PSR stream. Explicit
  request streams remain caller-owned; the client must not close or detach them.
- The client sends the exact bytes supplied by the request body, including
  empty strings, NULs, whitespace, and JSON formatting. It must not decode JSON
  or silently select a content type. A failed read must not be mistaken for an
  empty successful body.
- A response always yields a readable PSR stream with precisely the response
  body bytes, excluding status and header blocks. The client owns any stream it
  creates and must define who closes it and when; closing the transport must
  not invalidate the returned body.
- Distinguish transport failure from a valid HTTP error status. Preserve the
  original failure when cleanup also fails. Repeated requests must not reuse a
  stale body, status, or header block.
- Duplicate request and response headers, redirects, timeouts, proxy options,
  and bounded streaming need separate explicit fixtures before they can be
  claimed. The current `Curl` implementation buffers its result; this proposal
  does not claim a streaming guarantee.

## Maintainer choices needed before implementation

1. Should the PSR adapter create an empty stream for an omitted body, or should
   construction require a caller-supplied `StreamInterface`? The former keeps
   today's optional constructor argument but changes observable behavior.
2. Which component owns response stream creation and disposal, and what bounded
   buffering or streaming policy should `HTTPClient` expose? Do not imply that
   `Curl::option()` alone makes `HTTPClient` streaming-safe.
3. Should the exact-byte PSR adapter be isolated from legacy `Curl::query()`
   payload coercion, rather than changing the existing connection default?

After those choices, add red-before/green-after offline transport fixtures for
body bytes, empty and repeated requests, response body lifetime, duplicate
headers, failure cleanup, and streaming bounds. Keep the factory and GET-target
repairs in their separately reviewed slices.
