<?php
namespace BlueFission\Tests\HTML;

use BlueFission\HTML\Template;
use BlueFission\Parsing\Parser;
use BlueFission\Parsing\Registry\TagRegistry;
use BlueFission\Parsing\Registry\RendererRegistry;
use BlueFission\Parsing\Registry\ExecutorRegistry;
use BlueFission\Parsing\Registry\PreparerRegistry;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase {

    static $testdirectory = '../../testdirectory';

    static $classname = 'BlueFission\HTML\Template';

    static $file = 'sample.txt';
    static $layoutFile = 'layout.vibe';
    static $pageFile = 'page.vibe';

    static $configuration = [
        'file' => 'sample.txt',
        'template_directory' => '../../testdirectory',
        'cache' => true,
        'cache_expire' => 60,
        'cache_directory' => 'cache',
        'max_records' => 1000,
        'delimiter_start' => '{',
        'delimiter_end' => '}',
        'module_token' => 'mod',
        'module_directory' => 'modules',
        'format' => false,
        'eval' => false,
    ];

    protected $object;

    private function baseDir(): string
    {
        $baseDir = realpath(__DIR__.DIRECTORY_SEPARATOR.static::$testdirectory);
        if (!$baseDir) {
            $baseDir = realpath(static::$testdirectory);
        }

        return $baseDir ?: '';
    }

    public function setUp() :void {
        chdir(__DIR__);

        touch(static::$testdirectory.DIRECTORY_SEPARATOR.static::$file);

        $data = 'This is a sample text file';

        file_put_contents(static::$testdirectory.DIRECTORY_SEPARATOR.static::$file, $data);

        $this->object = new static::$classname(static::$configuration);
    }

    public function tearDown() :void {
        $testfiles = [
            static::$file,
            static::$layoutFile,
            static::$pageFile,
            'cache'.DIRECTORY_SEPARATOR.static::$file,
            'cache'
        ];

        foreach ($testfiles as $file) {
            if (is_dir(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.$file)) {
                @rmdir(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.$file);
            }

            if (file_exists(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.$file)) {
                @unlink(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.$file);
            }
        }
    }

    public function testConstructor() {
        $this->assertInstanceOf(Template::class, $this->object);
    }

    public function testLoad() {
        $this->assertTrue(is_string($this->object->contents()));
    }

    public function testContents() {
        $expected = 'This is a sample text file';
        $this->object->contents($expected);
        $this->assertEquals($expected, $this->object->contents());

        $actual = $this->object->contents();
        $this->assertEquals($expected, $actual);
    }
    
    public function testReset() {
        $expected = 'This is a sample text file';
        $this->object->contents($expected);
        $this->assertEquals($expected, $this->object->contents());

        $this->object->contents('Changed data');
        $this->assertNotEquals($expected, $this->object->contents());

        $this->object->reset();
        $this->assertEquals($expected, $this->object->contents());
    }

    public function testSet() {
        $this->object->contents('This should alter {test_var}.');
        $var = 'test_var';
        $content = 'This is a test';
        $formatted = true;
        $repetitions = 3;

        $this->object->set($var, $content, $formatted, $repetitions);
        
        $this->assertTrue(strpos($this->object->contents(), $content) !== false);
    }

    public function testRenderParsesVibeTags()
    {
        $baseDir = $this->baseDir();
        $this->assertNotSame('', $baseDir);
        $templateContents = "Hello {\$name}";

        $template = new Template([
            'file' => static::$pageFile,
            'template_directory' => $baseDir,
            'module_directory' => $baseDir,
        ]);
        $template->contents($templateContents);
        $template->assign(['name' => 'World']);

        $this->assertSame('Hello World', $template->render());
    }

    public function testRenderParsesTemplateSectionsFromFiles()
    {
        $baseDir = $this->baseDir();
        $this->assertNotSame('', $baseDir);
        $layoutPath = $baseDir.DIRECTORY_SEPARATOR.static::$layoutFile;
        $pagePath = $baseDir.DIRECTORY_SEPARATOR.static::$pageFile;

        file_put_contents($layoutPath, "Header:@output('main'):Footer");
        file_put_contents(
            $pagePath,
            "@template('layout.vibe')@section('main')Hello {\$name}@endsection"
        );

        $this->assertFileExists($layoutPath);
        $this->assertFileExists($pagePath);

        $template = new Template([
            'file' => static::$pageFile,
            'template_directory' => $baseDir,
            'module_directory' => $baseDir,
        ]);
        $template->assign(['name' => 'World']);

        $this->assertSame(
            "@template('layout.vibe')@section('main')Hello {\$name}@endsection",
            $template->contents()
        );

        $parser = new Parser($template->contents());
        TagRegistry::registerDefaults();
        RendererRegistry::registerDefaults();
        ExecutorRegistry::registerDefaults();
        PreparerRegistry::registerDefaults();
        $parser->setVariables(['name' => 'World']);
        $parser->setIncludePaths([
            'templates' => $baseDir,
            'modules' => $baseDir,
        ]);
        $this->assertSame('Header:Hello World:Footer', $parser->render());

        $this->assertSame('Header:Hello World:Footer', $template->render());
    }
}
