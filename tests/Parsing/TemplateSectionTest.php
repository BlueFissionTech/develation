<?php
namespace BlueFission\Tests\Parsing;

use BlueFission\Parsing\Parser;

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
}
