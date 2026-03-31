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

    public function testVarArrowRendersTransformedCloneWithoutMutatingBaseVariable()
    {
        $template = '{#let name="  john  "}{$name -> $.trim().capitalize()}|{$name}';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('John|  john  ', $output);
        $this->assertSame('  john  ', $parser->root()->getScopeVariable('name'));
    }

    public function testVarChainMutatesBaseVariable()
    {
        $template = '{#let name="  john  "}{$name.trim().capitalize()}|{$name}';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('John|John', $output);
        $this->assertSame('John', $parser->root()->getScopeVariable('name'));
    }

    public function testVarArrowRendersNestedMemberCloneWithoutMutation()
    {
        $template = '{$profile.value -> $.trim().capitalize()}|{$profile.value}';
        $parser = new Parser($template);
        $parser->setVariables([
            'profile' => ['value' => '  john  '],
        ]);
        $output = $parser->render();

        $this->assertSame('John|  john  ', $output);
        $this->assertSame(['value' => '  john  '], $parser->root()->getScopeVariable('profile'));
    }

    public function testVarChainMutatesNestedMemberPath()
    {
        $template = '{$profile.value.trim().capitalize()}|{$profile.value}';
        $parser = new Parser($template);
        $parser->setVariables([
            'profile' => ['value' => '  john  '],
        ]);
        $output = $parser->render();

        $this->assertSame('John|John', $output);
        $this->assertSame(['value' => 'John'], $parser->root()->getScopeVariable('profile'));
    }

    public function testVarArrowRendersObjectMemberCloneWithoutMutation()
    {
        $profile = new \stdClass();
        $profile->value = '  john  ';

        $template = '{$profile.value -> $.trim().capitalize()}|{$profile.value}';
        $parser = new Parser($template);
        $parser->setVariables([
            'profile' => $profile,
        ]);
        $output = $parser->render();

        $this->assertSame('John|  john  ', $output);
        $this->assertSame('  john  ', $parser->root()->getScopeVariable('profile')->value);
    }

    public function testVarChainMutatesObjectMemberPath()
    {
        $profile = new \stdClass();
        $profile->value = '  john  ';

        $template = '{$profile.value.trim().capitalize()}|{$profile.value}';
        $parser = new Parser($template);
        $parser->setVariables([
            'profile' => $profile,
        ]);
        $output = $parser->render();

        $this->assertSame('John|John', $output);
        $this->assertSame('John', $parser->root()->getScopeVariable('profile')->value);
    }

    public function testLetCanAssignTransformedExistingValueWithoutMutatingSource()
    {
        $template = '{#let name="  john  "}{#let title=name.trim().capitalize()}{$title}|{$name}';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('John|  john  ', $output);
        $this->assertSame('John', $parser->root()->getScopeVariable('title'));
        $this->assertSame('  john  ', $parser->root()->getScopeVariable('name'));
    }

    public function testLetCanAssignBackToExistingValue()
    {
        $template = '{#let name="  john  "}{#let name=name.trim().capitalize()}{$name}';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('John', $output);
        $this->assertSame('John', $parser->root()->getScopeVariable('name'));
    }

    public function testLetTransformThrowsWhenSourceValueIsMissing()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Cannot transform undefined value 'name'.");

        $parser = new Parser('{#let title=name.trim()}');
        $parser->render();
    }

    public function testTypedLetSupportsInlineJsonLiterals()
    {
        $template = '{#let settings:json=\'{"theme":"dark","layout":"wide"}\'}';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('', $output);
        $this->assertSame(
            ['theme' => 'dark', 'layout' => 'wide'],
            $parser->root()->getScopeVariable('settings')
        );
    }

    public function testTypedLetSupportsNestedInlineJsonLiterals()
    {
        $template = '{#let settings:json=\'{"theme":"dark","layout":{"width":"wide","columns":2}}\'}';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('', $output);
        $this->assertSame(
            [
                'theme' => 'dark',
                'layout' => ['width' => 'wide', 'columns' => 2],
            ],
            $parser->root()->getScopeVariable('settings')
        );
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
