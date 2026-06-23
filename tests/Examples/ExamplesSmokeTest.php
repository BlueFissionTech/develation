<?php

namespace BlueFission\Tests\Examples;

use BlueFission\Net\HTTP;
use PHPUnit\Framework\TestCase;

class ExamplesSmokeTest extends TestCase
{
    public function testCliReportExampleRuns(): void
    {
        [$exitCode, $output, $error] = $this->runExample([
            'examples/cli/report.php',
            '--limit',
            '2',
            '--delay',
            '0',
            '--title',
            'Smoke Report',
        ]);

        $this->assertSame(0, $exitCode, $error);
        $this->assertStringContainsString('Smoke Report', $output);
        $this->assertStringContainsString('Item 1', $output);
    }

    public function testHelperWorkflowExampleRuns(): void
    {
        [$exitCode, $output, $error] = $this->runExample(['examples/helpers/workflow.php']);

        $this->assertSame(0, $exitCode, $error);

        $data = HTTP::jsonDecode($output);

        $this->assertSame(3, $data['name_count']);
        $this->assertTrue($data['source_file_exists']);
        $this->assertTrue($data['source_file_reachable']);
        $this->assertTrue($data['admin_match']);
        $this->assertTrue($data['enabled_flag']);
        $this->assertSame('HTTP/1.1 200 OK', $data['status_line']);
        $this->assertSame('Example%20Report.md', $data['encoded_path_segment']);
    }

    public function testHttpApiPacketExampleRuns(): void
    {
        [$exitCode, $output, $error] = $this->runExample(['examples/http/api_packet.php']);

        $this->assertSame(0, $exitCode, $error);

        $data = HTTP::jsonDecode($output);

        $this->assertSame('GET', $data['request']['method']);
        $this->assertSame('https', $data['request']['scheme']);
        $this->assertSame('api.example.test', $data['request']['host']);
        $this->assertSame('Example%20Report.md', $data['request']['path_segment']);
        $this->assertSame('HTTP/1.1 202 Accepted', $data['expected_response']['status']);
        $this->assertStringStartsWith('api-example:', $data['content_id']);
    }

    public function testGameScriptExampleRunsWithoutInput(): void
    {
        [$exitCode, $output, $error] = $this->runExample(['examples/game/gangs.php', 'script']);

        $this->assertSame(0, $exitCode, $error);
        $this->assertStringContainsString('Scripted DevElation Gangs run.', $output);
        $this->assertStringContainsString('Recap of NPC actions:', $output);
    }

    public function testWebExamplesRenderWithoutPostData(): void
    {
        foreach ([
            'examples/todo/index.php' => 'DevElation Todo List',
            'examples/comments/index.php' => 'DevElation Comment Thread',
        ] as $script => $heading) {
            [$exitCode, $output, $error] = $this->runExample([$script]);

            $this->assertSame(0, $exitCode, $error);
            $this->assertStringContainsString($heading, $output);
            $this->assertStringNotContainsString('{\\$', $output);
            $this->assertStringNotContainsString('{@', $output);
        }
    }

    /**
     * @param array<int, string> $arguments
     * @return array{0:int,1:string,2:string}
     */
    private function runExample(array $arguments): array
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

        return [proc_close($process), (string)$output, (string)$error];
    }

    private function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
