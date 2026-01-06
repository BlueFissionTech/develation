<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use BlueFission\HTML\Template;
use BlueFission\HTML\Form;
use BlueFission\HTML\Table;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\Data\Log;
use BlueFission\Data\Storage\Session;

// Backing store: DevElation Session storage for threads.
// This could be any IData/Storage (file, DB, spreadsheet adaptor) injected
// in place of Session to persist threads with the same interface.
$session = new Session(['name' => 'develation_comments']);
$session->activate()->read();

// Simple logger to track comment events; storage type remains swappable.
$logger = Log::instance();
$logger->config(['instant' => true]);

// Retrieve existing threads from the session object, defaulting to a basic structure.
$storedThreads = $session->field('threads');
$threadsArr = new Arr($storedThreads ?? ['main' => []]);

if (!$threadsArr->hasKey('main')) {
    $threadsArr->set('main', []);
}

$action = $_POST['action'] ?? null;

if ($action === 'add') {
    $author = trim((string)($_POST['author'] ?? ''));
    $body = trim((string)($_POST['body'] ?? ''));
    if ($body !== '') {
        $id = uniqid('comment_', true);
        $main = new Arr($threadsArr->get('main'));
        $comment = [
            'id' => $id,
            'author' => $author !== '' ? $author : 'anonymous',
            'body' => $body,
            'votes' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $main->set($id, $comment);
        $threadsArr->set('main', $main->val());

        $logger->push("comment.created: {$comment['author']} – " . Str::truncate($comment['body'] ?? '', 80));
    }
} elseif ($action === 'upvote') {
    $id = $_POST['id'] ?? '';
    $main = new Arr($threadsArr->get('main'));
    if ($main->hasKey($id)) {
        $comment = $main->get($id);
        $comment['votes'] = ($comment['votes'] ?? 0) + 1;
        $main->set($id, $comment);
        $threadsArr->set('main', $main->val());

        $logger->push("comment.upvoted: {$comment['id']} [votes={$comment['votes']}]");
    }
}

// Persist updated thread list. Swapping Session for another Storage/Data
// implementation does not change the rest of the controller logic.
$session->assign(['threads' => $threadsArr->val()]);
$session->write();

$template = new Template([
    'file' => 'thread.vibe',
    'template_directory' => __DIR__ . '/templates',
    'module_directory' => __DIR__ . '/templates',
]);

$viewData = [
    'title' => 'DevElation Comment Thread',
];

// Build add-comment form via HTML\Form.
$addForm = '';
$addForm .= Form::open('', 'comment_add', 'post');
$addForm .= Form::field('text', 'author', 'Name', '');
$addForm .= Form::field('textarea', 'body', 'Comment', '');
$addForm .= Form::field('hidden', 'action', '', 'add');
$addForm .= Form::field('submit', 'submit', '', 'Post comment');
$addForm .= Form::close();
$viewData['add_form'] = $addForm;

// Build table of comments via HTML\Table.
$items = array_values($threadsArr->get('main') ?? []);

if ($items) {
    usort($items, function (array $a, array $b): int {
        if ($a['votes'] === $b['votes']) {
            return strcmp($b['created_at'], $a['created_at']);
        }
        return $b['votes'] <=> $a['votes'];
    });
}

$count = count($items);
// Append space so Template::field() does not treat zero as empty.
$viewData['comment_count'] = $count . ' ';
$viewData['comment_label'] = Str::pluralize('comment');

$rows = [];
foreach ($items as $comment) {
    $actions = '';
    $actions .= Form::open('', 'comment_vote_' . $comment['id'], 'post', false, 'class="inline-form"');
    $actions .= Form::field('hidden', 'id', '', $comment['id']);
    $actions .= Form::field('hidden', 'action', '', 'upvote');
    $actions .= Form::field('submit', 'submit', '', '▲', false, '', false, ['class' => 'secondary']);
    $actions .= Form::close();

    $rows[] = [
        $comment['votes'],
        $comment['author'] . ' · ' . $comment['created_at'],
        $comment['body'],
        $actions,
    ];
}

$table = new Table([
    'columns' => 4,
    'headers' => ['Votes', 'Meta', 'Comment', 'Actions'],
]);
$table->content($rows);
$viewData['items_table'] = $table->render();

$template->assign($viewData);

echo $template->render();
