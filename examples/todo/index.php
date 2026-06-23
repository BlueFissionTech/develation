<?php

declare(strict_types=1);

require __DIR__ . '/../support.php';

use BlueFission\HTML\Template;
use BlueFission\HTML\Form;
use BlueFission\HTML\Table;
use BlueFission\Date;
use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\Data\Storage\Session;

// Backing store: DevElation Session storage instead of raw $_SESSION.
// Any IData/Storage implementation (files, DB, etc.) can be swapped in here
// without changing the rest of the code, thanks to the shared interface.
$session = new Session(['name' => 'develation_todo']);
$session->activate()->read();

// Example logs are kept under .localappdata/examples so the package root stays tidy.
$logger = bf_example_logger('todo');

// Session data is stored as an Obj/Arr; retrieve the 'todos' field if present.
$storedTodos = $session->field('todos');
$todosArr = new Arr($storedTodos ?? []);

$action = bf_example_input('action');

if (Str::match($action, 'add', Str::IGNORE_CASE)) {
    $title = bf_example_input('title');
    $owner = bf_example_input('owner');
    $dueInput = bf_example_input('due');

    if ($title !== '') {
        $id = bf_example_id('todo');
        $owner = $owner !== '' ? $owner : 'guest';

        $dueDisplay = null;
        $overdue = false;

        if ($dueInput !== '') {
            $dueDate = new Date($dueInput);
            $dueTimestamp = $dueDate->timestamp();
            $nowTimestamp = Date::now()->timestamp();

            if ($dueTimestamp !== null) {
                $dueDisplay = Date::formatTimestamp($dueTimestamp);
                $overdue = $dueTimestamp < $nowTimestamp;
            }
        }

        $todosArr->set($id, [
            'id' => $id,
            'title' => $title,
            'done' => false,
            'owner' => $owner,
            'due' => $dueDisplay,
            'overdue' => $overdue,
        ]);

        $logger->push("todo.created: {$title} [owner={$owner}, due={$dueDisplay}]");
    }
} elseif (Str::match($action, 'toggle', Str::IGNORE_CASE)) {
    $id = bf_example_input('id');
    if ($todosArr->hasKey($id)) {
        $todo = $todosArr->get($id);
        $todo['done'] = !($todo['done'] ?? false);
        $todosArr->set($id, $todo);

        $logger->push("todo.toggled: {$todo['title']} [done=" . ($todo['done'] ? '1' : '0') . ']');
    }
} elseif (Str::match($action, 'delete', Str::IGNORE_CASE)) {
    $id = bf_example_input('id');
    if ($todosArr->hasKey($id)) {
        $todo = $todosArr->get($id);
        $todosArr->delete($id);
        $logger->push("todo.deleted: {$todo['title']}");
    }
}

// Persist back through DevElation storage. This is where you could swap
// in a different datasource (file, MySQL, spreadsheet) via dependency
// injection while keeping the controller logic identical.
$session->assign(['todos' => $todosArr->val()]);
$session->write();

$template = new Template([
    'file' => 'todo.vibe',
    'template_directory' => __DIR__ . '/templates',
    'module_directory' => __DIR__ . '/templates',
]);

$viewData = [
    'title' => 'DevElation Todo List',
];

// Total count via Arr instance; append space so Template::field() does not treat zero as empty.
$viewData['total_count'] = Arr::count($todosArr->val()) . ' ';

$items = [];
foreach ($todosArr->val() ?? [] as $todo) {
    $owner = $todo['owner'] ?? 'guest';
    $due = $todo['due'] ?? null;
    $overdue = (bool)($todo['overdue'] ?? false);

    $metaParts = [$owner];
    if ($due) {
        $metaParts[] = 'due ' . $due;
    }
    if ($overdue) {
        $metaParts[] = 'OVERDUE';
    }

    $metaParts = Arr::make($metaParts)
        ->filter(fn (string $part): bool => Str::isNotEmpty($part))
        ->values()
        ->val();

    $items[] = [
        'id' => $todo['id'],
        'title' => $todo['title'],
        'status_label' => $todo['done'] ? '[x]' : '[ ]',
        'owner' => $owner,
        'due' => $due,
        'overdue' => $overdue,
        'meta' => implode(' | ', $metaParts),
        'css_class' => Str::trim(($todo['done'] ? 'done ' : '') . ($overdue ? 'overdue' : '')),
    ];
}

// Use Arr pipeline for counting overdue tasks.
$overdueItems = Arr::make($items)->filter(fn (array $item): bool => !empty($item['overdue']));
$viewData['overdue_count'] = Arr::count($overdueItems->val()) . ' ';

// Build forms and table using DevElation HTML helpers.
$addForm = '';
$addForm .= Form::open('', 'todo_add', 'post');
$addForm .= Form::field('text', 'title', 'Task', '', true);
$addForm .= Form::field('text', 'owner', 'Owner', '');
$addForm .= Form::field('text', 'due', 'Due (YYYY-MM-DD)', '');
$addForm .= Form::field('hidden', 'action', '', 'add');
$addForm .= Form::field('submit', 'submit', '', 'Add');
$addForm .= Form::close();
$viewData['add_form'] = $addForm;

$rows = [];
foreach ($items as $item) {
    $actions = '';

    // Toggle form
    $actions .= Form::open('', 'todo_toggle_' . $item['id'], 'post', false, 'class="inline-form"');
    $actions .= Form::field('hidden', 'id', '', $item['id']);
    $actions .= Form::field('hidden', 'action', '', 'toggle');
    $actions .= Form::field('submit', 'submit', '', $item['status_label'], false, '', false, ['class' => 'secondary']);
    $actions .= Form::close();

    // Delete form
    $actions .= Form::open('', 'todo_delete_' . $item['id'], 'post', false, 'class="inline-form"');
    $actions .= Form::field('hidden', 'id', '', $item['id']);
    $actions .= Form::field('hidden', 'action', '', 'delete');
    $actions .= Form::field('submit', 'submit', '', 'Delete', false, '', false, ['class' => 'secondary']);
    $actions .= Form::close();

    $rows[] = [
        bf_example_html($item['title']),
        bf_example_html($item['meta']),
        bf_example_html($item['due'] ?? ''),
        bf_example_html($item['status_label']),
        $actions,
    ];
}

$table = new Table([
    'columns' => 5,
    'headers' => ['Task', 'Info', 'Due', 'Status', 'Actions'],
]);
$table->content($rows);
$viewData['items_table'] = $table->render();

$template->assign($viewData);

echo $template->render();
