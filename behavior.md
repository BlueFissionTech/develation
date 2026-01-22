# Behaviors.md

## Overview

The BlueFission Behavioral library provides a powerful event-driven architecture to manage the interactions of behaviors within applications and a robust foundation for building complex, event-driven applications. Behaviors encompass a wide range of functionalities, including events, states, and actions, which are fundamental to creating responsive, dynamic, and scalable applications. This document outlines how these behaviors are implemented and utilized within the library, explaining the differences between events, states, and actions, and how data is passed along during their execution. By abstracting behaviors into events, states, and actions, it allows developers to create modular, easy-to-manage code that is both efficient and scalable. The use of data encapsulation with `Meta` objects further enhances the flexibility and usability of the system.

### Definitions

- **Behavior**: The base class from which all other behaviors inherit. A behavior can be anything that changes the state or triggers an event in the system. It is characterized by attributes such as persistence (whether the behavior continues to affect the system after execution) and passivity (whether the behavior awaits an event to trigger).

- **Event**: A subclass of Behavior. Events are triggered by the system or by user interactions to signify that something has occurred. Examples include `OnLoad`, `OnSave`, or `OnComplete`.

- **State**: Also a subclass of Behavior, used to represent the state of an object within the system. States persist longer than events and define the current condition or status of objects, such as `IsLoading`, `IsError`, or `IsAuthenticated`.

- **Action**: Represents operations or commands that an object can perform, driven by events or changes in state. Actions might include `DoSave`, `DoDelete`, or `DoUpdate`.

### Data Handling

Data within the system is handled using a concept called `Meta`, a simple Data Transfer Object (DTO) or struct that encapsulates arguments in a standardized way. This allows events, actions, and states to be triggered or changed along with relevant data. For instance:

```php
$this->perform(State::PERFORMING_ACTION, new Meta(when: Action::READ, info: 'Opening the file for reading', data: $path));
```

In this example, `Meta` holds information about the action being performed, additional context (info), and relevant data (file path).

### Quick Start (Behaves)

```php
use BlueFission\Behavioral\Behaves;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;

class Worker {
    use Behaves;

    public function __construct() {
        $this->when(Event::PROCESSED, function($behavior, Meta $meta) {
            echo "Processed: " . json_encode($meta->data) . PHP_EOL;
        });
    }

    public function run(array $payload) {
        $this->dispatch(Event::PROCESSED, new Meta(data: $payload));
    }
}

(new Worker())->run(['id' => 42]);
```

### Event-Driven Architecture

#### Triggering Events

Events are triggered using the `dispatch` method, which can initiate any event along with its associated data. This makes it possible to handle events dynamically based on runtime conditions and data.

```php
$this->dispatch(Event::SAVED, new Meta(data: $userData));
```

#### Handling States

States are managed by transitioning from one state to another, ensuring that state changes are predictable and manageable. States can be used to control the flow of execution within the application, making it easier to maintain and understand.

```php
$this->perform(State::BUSY);
try {
    $this->perform(Action::PROCESS_DATA);
} finally {
    $this->halt(State::BUSY);
}
```

#### Executing Actions

Actions are direct manipulations or commands executed within the application. They can be triggered by events or state changes. Actions are defined with a clear intention of what they will perform, making them distinct and reusable.

```php
$this->perform(Action::DELETE, new Meta(info: 'User requested deletion'));
```

### Utilization

To utilize these behaviors, an object within the system should be capable of registering behaviors, triggering events, changing states, and performing actions. This is typically achieved through a behavior management system where behaviors are registered with specific triggers and conditions.

#### Example Usage

Here’s a simple example of how to define and trigger behaviors within an application:

```php
$object->behavior(new Event(Event::LOAD));
$object->when(Event::LOAD, function() {
    echo "Loaded successfully.";
});

$object->dispatch(Event::LOAD);
```

#### Extended Example
Here is an example of how an eCommerce checkout process might define and trigger behaviors within an application:

```php
$order->behavior(new Event(Event::CHECKOUT_STARTED));
$order->behavior(new State(State::PROCESSING_PAYMENT));
$order->behavior(new Action(Action::CHARGE_CARD));
$order->behavior(new Event(Event::ORDER_CONFIRMED));
$order->behavior(new Action(Action::SEND_EMAIL));

$order->when(Event::CHECKOUT_STARTED, function() use ($order) {
    echo "Checkout started.\n";
    $order->perform(State::PROCESSING_PAYMENT);
});

$order->when(State::PROCESSING_PAYMENT, function() use ($order) {
    echo "Processing payment...\n";
    $order->perform(Action::CHARGE_CARD, new Meta(data: ['card' => '**** **** **** 1234']));
});

$order->when(Action::CHARGE_CARD, function($behavior, $meta) use ($order) {
    echo "Charging card: " . $meta->data['card'] . "\n";
    $order->perform(Event::ORDER_CONFIRMED);
});

$order->when(Event::ORDER_CONFIRMED, function() use ($order) {
    echo "Order confirmed!\n";
    $order->perform(Action::SEND_EMAIL, new Meta(data: ['to' => 'customer@example.com']));
});

$order->when(Action::SEND_EMAIL, function($behavior, $meta) {
    echo "Sending email to " . $meta->data['to'] . "\n";
});

```

### What's Happening

- CHECKOUT_STARTED event is dispatched.
- The system enters PROCESSING_PAYMENT state.
- It performs CHARGE_CARD action using card info.
- Once the card is charged, it triggers ORDER_CONFIRMED event.
- Then it performs SEND_EMAIL action to email the customer.
