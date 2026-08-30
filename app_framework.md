# Application Framework

DevElation's `Services` namespace provides a lightweight application shell for routing, service dispatch, and behavior-driven workflows. It is not a full MVC framework, but it gives you consistent primitives for routing and messaging.

## Key Classes

- `Application`: entry point for routing, behavior dispatch, and service registry.
- `Service`: base service wrapper for behavior-based handlers.
- `Mapping`: route definition with method, path, and callable.
- `Gateway`: optional request preprocessing before service execution.
- `Uri`: URI parsing and matching helper.
- `Request` and `Response`: service request/response containers.

## Quick Start: Map and Run

```php
use BlueFission\Services\Application;

$app = new Application(['name' => 'Demo']);

$app->map('get', '/health', function() {
    return 'ok';
});

$app->args()->process()->run();
```

## Gateway Outcomes

Gateways may continue normally by returning `null` or
`GatewayOutcome::proceed()`. Return `GatewayOutcome::halt($response)` when the
mapped callable must not run. A gateway that needs exception-based control flow
may throw `GatewayHaltException($response)` instead.

The application records the first halt and stops the remaining gateway chain.
`run()` remains fluent and does not render the response; hosts can inspect
`$app->gatewayOutcome()->response()` and retain ownership of HTTP status,
headers, redirects, or other transport behavior. Calling `process()` again
starts a fresh gateway chain and clears the prior outcome.

## Service Dispatch Pattern

```php
use BlueFission\Services\Application;
use BlueFission\Services\Service;

class UserService extends Service {
    public function list() {
        return json_encode(['users' => []]);
    }
}

$app = new Application(['name' => 'Api']);
$app->delegate('users', UserService::class);
$app->map('get', '/users', [UserService::class, 'list']);

$app->args()->process()->run();
```

## Related

For data access, see `data_management.md`.
For networking utilities, see `network_services.md`.
