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

## Application Instances and Dependency Bindings

Application instances are owned by both their concrete application class and
their configured name. Repeated `getInstance()` calls reuse that exact owner;
an `Application` and an `Application` subclass may therefore use the same name
without sharing services, bindings, arguments, or lifecycle state.

```php
interface ClockContract {}
class SystemClock implements ClockContract {}

$app = Application::getInstance('Api');
$app->bind(ClockContract::class, SystemClock::class);
$clock = $app->resolve(ClockContract::class);
```

Instance calls always resolve from that instance. For static construction,
pass the owning instance or, when calling the appropriate application class,
its registered name:

```php
$service = Application::makeInstance(ServiceUsingClock::class, $app);
$sameService = Application::makeInstance(ServiceUsingClock::class, 'Api');
```

Without an explicit selector, static construction uses the default instance
of the called application class. Hosts with registration, argument, or boot
steps can label custom phases with `$app->lifecyclePhase('boot')`; an unbound
interface then raises `DependencyResolutionException` with machine-readable
dependency, phase, application, and resolved-class context.

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
