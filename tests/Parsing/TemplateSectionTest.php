<?php
namespace BlueFission\Tests\Parsing;

use BlueFission\Parsing\Parser;
use BlueFission\Tests\Support\TestEnvironment;

class TemplateSectionTest extends ParsingTestCase
{
    protected function setUp(): void
    {
        $this->registerParsingDefaults();
    }

    private function createTempDir(string $prefix): string
    {
        $base = __DIR__ . DIRECTORY_SEPARATOR . '_tmp';
        if (!is_dir($base)) {
            mkdir($base);
        }
        $dir = $base . DIRECTORY_SEPARATOR . $prefix . '_' . uniqid();
        mkdir($dir);

        return $dir;
    }

    public function testTemplateSectionOutputFlow()
    {
        $dir = $this->createTempDir('template');
        $layoutPath = $dir . DIRECTORY_SEPARATOR . 'layout.vibe';
        file_put_contents($layoutPath, "Header:@output('main'):Footer");

        $template = "@template('layout.vibe')@section('main')Hello {\$name}@endsection";
        $parser = new Parser($template);
        $parser->setIncludePaths(['templates' => $dir]);
        $parser->setVariables(['name' => 'World']);
        $output = $parser->render();

        $this->assertSame('Header:Hello World:Footer', $output);
    }

    public function testTemplateSectionCanIncludeExternalPartial()
    {
        $dir = $this->createTempDir('template_include');
        $layoutPath = $dir . DIRECTORY_SEPARATOR . 'layout.vibe';
        $partialPath = $dir . DIRECTORY_SEPARATOR . 'partial.vibe';

        file_put_contents($layoutPath, "Header:@output('main'):Footer");
        file_put_contents($partialPath, 'Hi {$name}');

        $template = "@template('layout.vibe')@section('main')@include('partial.vibe')@endsection";
        $parser = new Parser($template);
        $parser->setIncludePaths([
            'templates' => $dir,
            'modules' => $dir,
        ]);
        $parser->setVariables(['name' => 'World']);
        $output = $parser->render();

        $this->assertSame('Header:Hi World:Footer', $output);
    }

    public function testMissingTemplateReportsReadFailure()
    {
        $dir = TestEnvironment::tempDir('bf_missing_template');

        try {
            $parser = new Parser("@template('missing layout.vibe')");
            $parser->setIncludePaths(['templates' => $dir]);

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Template "missing layout.vibe" could not be read.');
            $parser->render();
        } finally {
            TestEnvironment::removeDir($dir);
        }
    }

    public function testEmptyTemplateIsNotAReadFailure()
    {
        $dir = TestEnvironment::tempDir('bf_empty_template');
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'empty layout.vibe', '');

        try {
            $parser = new Parser("@template('empty layout.vibe')");
            $parser->setIncludePaths(['templates' => $dir]);

            $this->assertSame('', $parser->render());
        } finally {
            TestEnvironment::removeDir($dir);
        }
    }
}
