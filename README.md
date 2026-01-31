# DevElation: A PHP Library for Graceful Development

Welcome to the central documentation of **DevElation**, a comprehensive PHP library designed to simplify the development of large and complex projects. This library addresses the intricacies of modern PHP development with a suite of tools that cater to a wide range of functionalities, from basic data type handling to advanced system management and beyond. It is a property of Blue Fission Technology and is currently available under the MIT license.

## Philosophy

DevElation is built with the philosophy of reducing code complexity and promoting interconnectedness. It embraces the notion that robust and intelligent application development can be made more approachable through a suite of well-organized, intuitive modules. With a focus on automation, smart application components, and AI integration readiness, DevElation aims to be the cornerstone for framework builders in these domains.

Central to DevElation's design is the principle of rapid prototyping, enabled by a consistent interface across its wide range of classes. Whether you're interacting with MySQL, Mongo, File, or Session storage, the method signatures and arguments are intentionally aligned. This uniformity means that developers can swap out entire data layers with minimal changes to the codebase, streamlining the process of scaling applications from simple prototypes to complex, robust systems.

The library's dependency injection-friendly architecture means that scaling and enhancing functionality is as simple as changing the injected class. For example, an application initially designed with File storage can seamlessly transition to a Mongo database by substituting the File class with a Mongo class, with no need for extensive codebase changes. This not only accelerates development time but also promotes a clean and modular approach to application design.

The pervasive event-driven approach throughout DevElation—where even the simplest data types are empowered with event handling capabilities—speaks to the library's commitment to creating intricate yet manageable systems. By offloading complexity to individual components, each element of the codebase contributes to a finely-tuned orchestration of operations. The result is a system where functionality is distributed yet cohesive, ensuring that enhancements can be made without increasing the burden on the core logic of the application.

This granular empowerment leads to a development environment where maintainability and readability are not at odds with the power and sophistication of the systems being developed. With DevElation, creating dynamic, intelligent, and complex functionalities doesn't lead to cluttered code; instead, it fosters an ecosystem where each piece intelligently contributes to the whole.

Furthermore, DevElation isn't just a standalone library; it forms the backbone of the BlueFission Opus project development framework. Opus leverages DevElation's core capabilities, extending them into a full-fledged framework that supports the construction of advanced and intelligent web applications. The shared philosophy underpinning both DevElation and Opus ensures that developers who are familiar with DevElation can effortlessly transition to using the Opus framework, with the assurance that they are building on top of a reliable and proven foundation.

## Features Overview

### Event Handling (`Behavior`)
DevElation implements a behavior-driven event handling system, which includes `Event`, `State`, and `Action`. These behaviors allow for reactive and decoupled components, making your application more modular and maintainable.

- [Event Handling Documentation](behavior.md)

### Data Types
A collection of wrapper classes around PHP's primitive data types that offer enhanced functionality and utility methods.

- [Data Types Documentation](datatypes.md)
- [Library Overview](library_overview.md)

Static helpers: most `Val`/`Obj`-based classes expose their underscored instance helpers as static shorthand. For example, `Str` has an internal `_pluralize()` instance method which can be invoked statically via `Str::pluralize('comment')` thanks to `Val::__callStatic`. This pattern is used throughout the library and examples.

### DateTime Handling
Sophisticated date and time manipulation with object-oriented principles, extending PHP's native `DateTime` class.

- [DateTime Documentation](date.md)

### Complex Object Prototyping
An advanced object class, `Obj`, extends the capabilities of `Val` for managing complex data structures.

- [Object Prototyping Documentation](object.md)

### Collections
Manage groups of items with powerful collection classes, providing utilities for array-like operations on objects.

- [Collections Documentation](collections.md)

### Connections
Facilitate connectivity to various data sources and services, such as MySQL, cURL, and streams.

- [Connections Documentation](connections.md)

### Data Management
Handle queues, storage solutions, databases, Redis, MemQ, files, and logs with a unified approach to data manipulation and persistence.

- [Data Management Documentation](data_management.md)
- [Schema Documentation](schema.md)
- [Graph Documentation](graph.md)

### HTML Building Tools
Construct HTML elements for forms, tables, and templating with ease, improving the speed of frontend development.

- [HTML Tools Documentation](html_tools.md)

### Parsing & Templating
Vibe parsing, tag execution, includes, and template sections for declarative templating workflows.

- [Parsing & Templating Documentation](parsing.md)

### Network Services
Tools for handling email, HTTP requests, and IP operations, essential for developing web services and networked applications.

- [Network Services Documentation](network_services.md)

### Application Framework
Beginnings of an application framework, offering service and routing management to pave the way for complex application architectures.

- [Application Framework Documentation](app_framework.md)

### System Tools
A suite of tools for interacting with the operating system, including CLI utilities, process control, machine details, statistics, and asynchronous operations.

- [System Tools Documentation](system_tools.md)

### Utilities
A set of utility functions and tools for administrative alerts, logging, IP blocking, and safeguards against runaway scripts.

- [Utilities Documentation](utilities.md)

### Security Tools
Hashing helpers for checksums, content IDs, and signatures.

- [Security Documentation](security.md)

### Testing
Guidance for running PHPUnit tests and enabling optional integration coverage.

- [Testing Documentation](tests.md)
- [Coding Standards](coding_standards.md)

### Examples
Sample applications that demonstrate DevElation’s flexibility:

- Session-backed todo list using `Arr`, `Date`, `Session` storage, HTML helpers, and Vibe templates: `examples/todo/index.php`
- Simple comment thread with voting using `Arr`, `Str`, `Session` storage, HTML helpers, and Vibe templates: `examples/comments/index.php`
- CLI territory game using the behavioral engine (`Behaves`) and an `Arr`-backed log: `examples/game/gangs.php`
- CLI status report using args, tables, and progress bars: `examples/cli/report.php`
- Additional walkthroughs live in `examples/README.md`

## Quick Start Examples

### CLI utilities: args, tables, and progress

```php
use BlueFission\Cli\Args;
use BlueFission\Cli\Args\OptionDefinition;
use BlueFission\Cli\Console;

$args = (new Args())
    ->addOption(new OptionDefinition('limit', [
        'short' => 'l',
        'type' => 'int',
        'default' => 5,
        'description' => 'Number of rows to show.',
    ]))
    ->parse($argv);

$options = $args->options();
$limit = $options['limit'] ?? 5;

$console = new Console();
$console->writeln($console->color('Report', 'cyan', ['bold']));
$console->writeln($console->table(['Item', 'Count'], [
    ['alpha', $limit],
]));

for ($i = 1; $i <= $limit; $i++) {
    $console->rewriteLine($console->progress($limit, $i));
}
$console->writeln('');
```

### Storage pipeline: session-backed data

```php
use BlueFission\Data\Storage\Session;

$store = new Session(['name' => 'todos']);
$store->activate()->read();

$todos = (array)($store->contents() ?? []);
$todos[] = ['task' => 'Ship docs', 'due' => '2026-01-10'];

$store->assign($todos);
$store->write();
```

## Usage

As of now, DevElation does not have a composer package and is not available on Packagist. It can be utilized by cloning the repository `bluefission/develation` from GitHub.

```bash
git clone https://github.com/bluefission/develation.git
```

## Contributions

DevElation welcomes contributions from the open-source community. Whether you're a seasoned developer or just starting, your input is valued. If you have ideas on how to expand the library's capabilities, especially in areas of automation, smart technologies, and AI, please consider contributing.
