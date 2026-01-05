<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use BlueFission\HTML\Template;
use BlueFission\HTML\Form;
use BlueFission\HTML\Table;
use BlueFission\Date;
use BlueFission\Arr;
use BlueFission\Data\Log;
use BlueFission\Collections\Collection;
use BlueFission\Data\Storage\Session;

// Backing store: DevElation Session storage instead of raw $_SESSION.
// Any IData/Storage implementation (files, DB, etc.) can be swapped in here
// without changing the rest of the code, thanks to the shared interface.
$session = new Session(['name' => 'develation_todo']);
$session->activate()->read();

// Simple file logger; could also be swapped for email/system logging via config.
$logger = Log::instance();
$logger->config(['instant' => true]);

// Session data is stored as an Obj/Arr; retrieve the 'todos' field if present.
$storedTodos = $session->field('todos');
$todosArr = new Arr($storedTodos ?? []);

$action = $_POST['action'] ?? null;

if ($action === 'add') {
    $title = trim((string)($_POST['title'] ?? ''));
    $owner = trim((string)($_POST['owner'] ?? ''));
    $dueInput = trim((string)($_POST['due'] ?? ''));

    if ($title !== '') {
        $id = uniqid('todo_', true);
        $owner = $owner !== '' ? $owner : 'guest';

        $dueDisplay = null;
        $overdue = false;

        if ($dueInput !== '') {
            $dueDate = new Date($dueInput);
            $dueTimestamp = $dueDate->timestamp();
            $nowTimestamp = Date::now()->timestamp();

            if ($dueTimestamp !== null) {
                $dueDisplay = $dueDate->date();
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
} elseif ($action === 'toggle') {
    $id = $_POST['id'] ?? '';
    if ($todosArr->hasKey($id)) {
        $todo = $todosArr->get($id);
        $todo['done'] = !($todo['done'] ?? false);
        $todosArr->set($id, $todo);

        $logger->push("todo.toggled: {$todo['title']} [done=" . ($todo['done'] ? '1' : '0') . ']');
    }
} elseif ($action === 'delete') {
    $id = $_POST['id'] ?? '';
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
$viewData['total_count'] = $todosArr->count() . ' ';

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

    $items[] = [
        'id' => $todo['id'],
        'title' => $todo['title'],
        'status_label' => $todo['done'] ? '[x]' : '[ ]',
        'owner' => $owner,
        'due' => $due,
        'overdue' => $overdue,
        'meta' => implode(' · ', array_filter($metaParts)),
        'css_class' => trim(($todo['done'] ? 'done ' : '') . ($overdue ? 'overdue' : '')),
    ];
}

// Use Arr pipeline for counting overdue tasks.
$overdueCollection = new Collection($items);
$overdueCollection = $overdueCollection->filter(function ($item) {
    return !empty($item['overdue']);
});
$viewData['overdue_count'] = $overdueCollection->count() . ' ';

// Build forms and table using DevElation HTML helpers.
$addForm = '';
$addForm .= Form::open('', 'todo_add', 'post');
$addForm .= Form::field('text', 'title', 'Task', '', true);
$addForm .= Form::field('text', 'owner', 'Owner', '');
$addForm .= Form::field('date', 'due', 'Due', '');
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
        $item['title'],
        $item['meta'],
        $item['due'] ?? '',
        $item['status_label'],
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
