# HTML Tools

The HTML helpers in DevElation focus on quick, server-side markup generation for forms, tables, templates, and XML. They pair well with Vibe templates and the parsing pipeline documented in `parsing.md`.

## Key Classes

- `HTML`: low-level helpers for formatting text, links, images, and tags.
- `Form`: form and input builders (text, select, checkbox, date, and more).
- `Table`: HTML table generator with optional headers and styling.
- `Template`: Vibe-based template rendering.
- `XML`: lightweight XML helpers.

## Quick Start: Form Fields

```php
use BlueFission\HTML\Form;

$form = Form::open('/submit', 'signup');
$form .= Form::field('text', 'email', 'Email');
$form .= Form::field('password', 'password', 'Password');
$form .= Form::field('submit', 'save', '', 'Create Account');
$form .= Form::close();

echo $form;
```

## Quick Start: Table Rendering

```php
use BlueFission\HTML\Table;

$table = new Table([
    'headers' => ['Name', 'Value'],
]);

$table->content([
    ['Framework', 'DevElation'],
    ['License', 'MIT'],
]);

echo $table->render();
```

## Templates

For file-based templates, use `HTML\Template` and the Vibe syntax documented in `parsing.md`.
