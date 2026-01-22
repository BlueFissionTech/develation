# Network Services

The `BlueFission\Net` namespace provides common HTTP, email, and IP utilities. These classes are lightweight and are meant to be composed with `Connections` or `Services` when you need richer behavior.

## Key Classes

- `HTTP`: helpers for queries, sessions, cookies, redirects, and URL inspection.
- `HTTPClient` and `HTTPClientFactory`: PSR-18 compatible HTTP client support.
- `Request` and `Response`: PSR-7 request/response implementations.
- `Email`: mail composition and delivery helpers.
- `IP`: IP address helpers and checks.

## Quick Start: HTTP Helpers

```php
use BlueFission\Net\HTTP;

$query = HTTP::query(['q' => 'search', 'page' => 2]);
$url = 'https://example.com/?' . $query;

if (HTTP::urlExists($url)) {
    echo "URL is reachable";
}
```

## Quick Start: Email

```php
use BlueFission\Net\Email;

Email::sendMail(
    'ops@example.com',
    'no-reply@example.com',
    'Job finished',
    'The nightly sync completed successfully.'
);
```

## Related

If you need connection lifecycle events and state handling, see `connections.md`.
