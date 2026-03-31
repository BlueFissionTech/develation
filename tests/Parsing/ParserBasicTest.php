<?php
namespace BlueFission\Tests\Parsing;

use BlueFission\Parsing\Parser;

class ParserBasicTest extends ParsingTestCase
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

    public function testLetAndVarOutput()
    {
        $template = '{#let foo="bar"}{$foo}';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('bar', $output);
    }

    public function testIfConditionOutputsMatchingBlock()
    {
        $template = '{#let status="ok"}{#if var=status equals="ok"}yes{/if}{#if var=status equals="no"}no{/if}';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('yes', $output);
    }

    public function testEachUsesCurrentAndIndex()
    {
        $template = '{#each items=items glue=","}{@index}:{@current}{/each}';
        $parser = new Parser($template);
        $parser->setVariables(['items' => ['a', 'b']]);
        $output = $parser->render();

        $this->assertSame('0:a,1:b', $output);
    }

    public function testEachExposesCurrentAsScopedVariableForNestedIterationInPartials()
    {
        $dir = $this->createTempDir('nested_sections');
        $partialPath = $dir . DIRECTORY_SEPARATOR . 'sections.vibe';
        file_put_contents($partialPath, '{#each items=current.sections glue=","}{.title}{/each}');

        $template = '{#each items=chapters glue="|"}{$current.title}:@include(\'sections.vibe\'){/each}';
        $parser = new Parser($template);
        $parser->setVariables([
            'chapters' => [
                [
                    'title' => 'Chapter 1',
                    'sections' => [
                        ['title' => 'Section 1'],
                        ['title' => 'Section 2'],
                    ],
                ],
                [
                    'title' => 'Chapter 2',
                    'sections' => [
                        ['title' => 'Section 3'],
                    ],
                ],
            ],
        ]);
        $parser->setIncludePaths(['modules' => $dir]);
        $output = $parser->render();

        $this->assertSame('Chapter 1:Section 1,Section 2|Chapter 2:Section 3', $output);
    }

    public function testEachSupportsCurrentAliasShorthand()
    {
        $template = '{#each items=items glue=","}{.title}{/each}';
        $parser = new Parser($template);
        $parser->setVariables([
            'items' => [
                ['title' => 'Alpha'],
                ['title' => 'Beta'],
            ],
        ]);
        $output = $parser->render();

        $this->assertSame('Alpha,Beta', $output);
    }

    public function testEvalAssignsVariableForLaterUse()
    {
        $template = '{=foo}{$foo}';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('foogenerated', $output);
        $this->assertSame('generated', $parser->root()->getScopeVariable('foo'));
    }

    public function testIncludeRendersModuleContent()
    {
        $dir = $this->createTempDir('include');
        $modulePath = $dir . DIRECTORY_SEPARATOR . 'module.vibe';
        file_put_contents($modulePath, 'Hello {$name}');

        $parser = new Parser("@include('module.vibe')");
        $parser->setVariables(['name' => 'World']);
        $parser->setIncludePaths(['modules' => $dir]);
        $output = $parser->render();

        $this->assertSame('Hello World', $output);
    }

    public function testImportProcessesVariablesFromFile()
    {
        $dir = $this->createTempDir('import');
        $importPath = $dir . DIRECTORY_SEPARATOR . 'vars.vibe';
        file_put_contents($importPath, '{#let foo="bar"}');

        $parser = new Parser("@import('vars.vibe'){\$foo}");
        $parser->setIncludePaths(['includes' => $dir]);
        $output = $parser->render();

        $this->assertSame('bar', $output);
    }

    public function testEvalCanLoadSourceFromIncludePaths()
    {
        $dir = $this->createTempDir('eval_src');
        $sourcePath = $dir . DIRECTORY_SEPARATOR . 'note.txt';
        file_put_contents($sourcePath, 'Loaded from file');

        $parser = new Parser("{=data src='note.txt'}");
        $parser->setIncludePaths(['includes' => $dir]);
        $parser->render();

        $this->assertSame('Loaded from file', $parser->root()->getScopeVariable('data'));
    }
}
