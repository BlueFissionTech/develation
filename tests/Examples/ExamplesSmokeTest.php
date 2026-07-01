<?php

namespace BlueFission\Tests\Examples;

use BlueFission\Net\HTTP;
use PHPUnit\Framework\TestCase;

class ExamplesSmokeTest extends TestCase
{
    public function testRunnableExamplesProduceExpectedOutput(): void
    {
        $workflow = HTTP::jsonDecode($this->runExample(['examples/helpers/workflow.php']));

        $this->assertSame('=== DevElation helper workflow ===', $workflow['title']);
        $this->assertSame(3, $workflow['name_count']);
        $this->assertTrue($workflow['source_file_exists']);
        $this->assertTrue($workflow['source_file_reachable']);
        $this->assertContains('names.txt', $workflow['fixture_entries']);
        $this->assertSame('Ada Lovelace', $workflow['collection_name_summaries'][0]['name']);
        $this->assertSame(0, $workflow['collection_name_summaries'][0]['index']);
        $this->assertSame('ada-lovelace', $workflow['collection_name_summaries'][0]['slug']);
        $this->assertTrue($workflow['admin_match']);
        $this->assertTrue($workflow['enabled_flag']);
        $this->assertSame('HTTP/1.1 200 OK', $workflow['status_line']);
        $this->assertSame('Example%20Report.md', $workflow['encoded_path_segment']);

        $packet = HTTP::jsonDecode($this->runExample(['examples/http/api_packet.php']));

        $this->assertSame('GET', $packet['request']['method']);
        $this->assertSame('https', $packet['request']['scheme']);
        $this->assertSame('api.example.test', $packet['request']['host']);
        $this->assertSame('Example%20Report.md', $packet['request']['path_segment']);
        $this->assertSame('HTTP/1.1 202 Accepted', $packet['expected_response']['status']);
        $this->assertStringStartsWith('api-example:', $packet['content_id']);

        $cli = $this->runExample([
            'examples/cli/report.php',
            '--limit',
            '2',
            '--delay',
            '0',
            '--title',
            'Smoke Report',
        ]);

        $this->assertStringContainsString('Smoke Report', $cli);
        $this->assertStringContainsString('Item 1', $cli);
        $this->assertStringContainsString('total: 2', $cli);

        $game = $this->runExample(['examples/game/gangs.php', 'script']);

        $this->assertStringContainsString('Scripted DevElation Gangs run.', $game);
        $this->assertStringContainsString('Territory:', $game);
        $this->assertStringContainsString('Recap of NPC actions:', $game);

        $todo = $this->runExample(['examples/todo/index.php']);

        $this->assertStringContainsString('<title>DevElation Todo List</title>', $todo);
        $this->assertStringContainsString('Session-backed list using DevElation Session storage', $todo);
        $this->assertStringContainsString('table class="dev_table"', $todo);
        $this->assertStringNotContainsString('{$', $todo);
        $this->assertStringNotContainsString('{@', $todo);

        $comments = $this->runExample(['examples/comments/index.php']);

        $this->assertStringContainsString('<title>DevElation Comment Thread</title>', $comments);
        $this->assertStringContainsString('Thread backed by DevElation Session storage', $comments);
        $this->assertStringContainsString('table class="dev_table"', $comments);
        $this->assertStringNotContainsString('{$', $comments);
        $this->assertStringNotContainsString('{@', $comments);
    }

    private function runExample(array $arguments): string
    {
        $pipes = [];
        $process = proc_open(
            array_merge([PHP_BINARY], $arguments),
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $this->projectRoot()
        );

        $this->assertIsResource($process);

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $status = proc_close($process);

        $this->assertSame(0, $status, $error);

        return $output;
    }

    private function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
