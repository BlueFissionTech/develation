<?php
namespace BlueFission\Tests\Parsing;

use BlueFission\Parsing\Contracts\IToolFunction;
use BlueFission\Parsing\Parser;
use BlueFission\Parsing\Registry\TagRegistry;
use BlueFission\Parsing\Registry\FunctionRegistry;
use BlueFission\Parsing\TagDefinition;
use BlueFission\Parsing\Elements;

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

    private function registerExtendedEvalTag(array $attributes = []): void
    {
        TagRegistry::register(new TagDefinition(
            name: 'eval',
            pattern: '{open}=(.*?)(?:->(\\w+))?(?:\\s+silent=[\'\"]?(true|false)[\'\"]?)?{close}',
            attributes: array_merge(
                ['expression', 'params', 'assign', 'silent', 'default', 'src', 'ref', 'profile', 'phase', 'label'],
                $attributes
            ),
            interface: \BlueFission\Parsing\Contracts\IRenderableElement::class,
            class: Elements\EvalElement::class
        ));
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

    public function testNestedEachRendersCorrectlyInSameFile()
    {
        $template = '{#each items=chapters glue="|"}{$current.title}:{#each items=current.sections glue=","}{.title}{/each}{/each}';
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
        $output = $parser->render();

        $this->assertSame('Chapter 1:Section 1,Section 2|Chapter 2:Section 3', $output);
    }

    public function testNestedEachWithIncludesRendersCorrectlyInSameFile()
    {
        $dir = $this->createTempDir('nested_same_file_include');
        $partialPath = $dir . DIRECTORY_SEPARATOR . 'section_label.vibe';
        file_put_contents($partialPath, '[{.title}]');

        $template = '{#each items=chapters glue="|"}{$current.title}:{#each items=current.sections glue=","}@include(\'section_label.vibe\'){/each}{/each}';
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

        $this->assertSame('Chapter 1:[Section 1],[Section 2]|Chapter 2:[Section 3]', $output);
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

    public function testEachGlueDecodesEscapedNewlines()
    {
        $template = '{#each items=items glue="\\n"}- {@current}{/each}';
        $parser = new Parser($template);
        $parser->setVariables(['items' => ['A', 'B']]);
        $output = $parser->render();

        $this->assertSame("- A\n- B", $output);
    }

    public function testEachGlueDecodesEscapedDoubleNewlines()
    {
        $template = '{#each items=items glue="\\r\\n\\r\\n"}{@current}{/each}';
        $parser = new Parser($template);
        $parser->setVariables(['items' => ['A', 'B']]);
        $output = $parser->render();

        $this->assertSame("A\r\n\r\nB", $output);
    }

    public function testEachGlueDecodesEscapedTabs()
    {
        $template = '{#each items=items glue="\\t"}{@current}{/each}';
        $parser = new Parser($template);
        $parser->setVariables(['items' => ['A', 'B']]);
        $output = $parser->render();

        $this->assertSame("A\tB", $output);
    }

    public function testEvalAssignsVariableForLaterUse()
    {
        $template = '{=foo}{$foo}';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('foogenerated', $output);
        $this->assertSame('generated', $parser->root()->getScopeVariable('foo'));
    }

    public function testAssignedEvalDoesNotRenderRawExpressionText()
    {
        FunctionRegistry::register(new class implements IToolFunction {
            public function name(): string
            {
                return 'capturePair';
            }

            public function execute(array $args): mixed
            {
                return implode(':', $args);
            }
        });

        $template = '{#let glossaryEntries=[]}{=capturePair(left, right) -> glossaryEntries[]}AFTER';
        $parser = new Parser($template);
        $parser->setVariables([
            'left' => 'Alpha',
            'right' => 'Beta',
        ]);
        $output = $parser->render();

        $this->assertSame('AFTER', $output);
        $this->assertSame(['Alpha:Beta'], $parser->root()->getScopeVariable('glossaryEntries'));
    }

    public function testAssignedEvalWithoutPushDoesNotRenderRawExpressionText()
    {
        FunctionRegistry::register(new class implements IToolFunction {
            public function name(): string
            {
                return 'capturePairSingle';
            }

            public function execute(array $args): mixed
            {
                return implode(':', $args);
            }
        });

        $template = '{=capturePairSingle(left, right) -> glossaryEntry}AFTER';
        $parser = new Parser($template);
        $parser->setVariables([
            'left' => 'Alpha',
            'right' => 'Beta',
        ]);
        $output = $parser->render();

        $this->assertSame('AFTER', $output);
        $this->assertSame('Alpha:Beta', $parser->root()->getScopeVariable('glossaryEntry'));
    }

    public function testAssignedEvalWithAttributesStillPushesIntoTarget()
    {
        FunctionRegistry::register(new class implements IToolFunction {
            public function name(): string
            {
                return 'captureAttributedPair';
            }

            public function execute(array $args): mixed
            {
                return implode(':', $args);
            }
        });

        $template = '{#let glossaryEntries=[]}{=captureAttributedPair(left, right) -> glossaryEntries[] ref="book" profile="main" phase="draft" label="Glossary"}AFTER';
        $parser = new Parser($template);
        $parser->setVariables([
            'left' => 'Alpha',
            'right' => 'Beta',
        ]);
        $output = $parser->render();

        $this->assertSame('AFTER', $output);
        $this->assertSame(['Alpha:Beta'], $parser->root()->getScopeVariable('glossaryEntries'));
    }

    public function testAssignedEvalWithAttributesStillAssignsScalarTarget()
    {
        FunctionRegistry::register(new class implements IToolFunction {
            public function name(): string
            {
                return 'captureAttributedSingle';
            }

            public function execute(array $args): mixed
            {
                return implode(':', $args);
            }
        });

        $template = '{=captureAttributedSingle(left, right) -> glossaryEntry ref="book" profile="main" phase="draft" label="Glossary"}AFTER';
        $parser = new Parser($template);
        $parser->setVariables([
            'left' => 'Alpha',
            'right' => 'Beta',
        ]);
        $output = $parser->render();

        $this->assertSame('AFTER', $output);
        $this->assertSame('Alpha:Beta', $parser->root()->getScopeVariable('glossaryEntry'));
    }

    public function testGeneratorEvalWithExtendedAttributesStillAssignsTarget()
    {
        $this->registerExtendedEvalTag();

        $template = '{=bookBlueprint -> generatedBook ref="example.ref" profile="editorial" phase="draft" label="Book"}AFTER';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('AFTER', $output);
        $this->assertSame('generated', $parser->root()->getScopeVariable('generatedBook'));
    }

    public function testGeneratorEvalWithAttributesBeforeAssignmentStillAssignsTarget()
    {
        $this->registerExtendedEvalTag();

        $template = '{=bookBlueprint ref="example.ref" profile="editorial" phase="draft" label="Book" -> generatedBook}AFTER';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('AFTER', $output);
        $this->assertSame('generated', $parser->root()->getScopeVariable('generatedBook'));
    }

    public function testQuotedEvalAttributesSupportScopedInterpolationFilters()
    {
        $this->registerExtendedEvalTag(['thread', 'session']);

        $template = '{=bookBlueprint -> generatedBook thread="book:[[book.slug|slug]]:chapter:[[chapter|pad:2]]:section:[[section.slug|slug]]" session="draft:[[section.title|trim|lower]]" label="Chapter [[chapter|pad:2]] / [[section.title|default:Untitled]]"}';
        $parser = new Parser($template);
        $parser->setVariables([
            'book' => ['slug' => 'My Great Book'],
            'chapter' => 3,
            'section' => [
                'slug' => 'Intro Section',
                'title' => '  Opening  ',
            ],
        ]);
        $parser->render();

        $element = $parser->root()->children()[0];

        $this->assertSame('book:my-great-book:chapter:03:section:intro-section', $element->getAttribute('thread'));
        $this->assertSame('draft:opening', $element->getAttribute('session'));
        $this->assertSame('Chapter 03 /   Opening  ', $element->getAttribute('label'));
    }

    public function testQuotedEvalAttributesUseDefaultsAndIgnoreUnknownFilters()
    {
        $this->registerExtendedEvalTag(['thread', 'session']);

        $template = '{=bookBlueprint -> generatedBook thread="book:[[book.title|unknown]]" session="section:[[section.title|default:Untitled]]"}';
        $parser = new Parser($template);
        $parser->setVariables([
            'book' => ['title' => 'My Book'],
        ]);
        $parser->render();

        $element = $parser->root()->children()[0];

        $this->assertSame('book:My Book', $element->getAttribute('thread'));
        $this->assertSame('section:Untitled', $element->getAttribute('session'));
    }

    public function testQuotedEachGlueAttributesSupportInterpolation()
    {
        $template = '{#each items=items glue="[[separator|default:,]]"}{@current}{/each}';
        $parser = new Parser($template);
        $parser->setVariables([
            'items' => ['A', 'B'],
            'separator' => '|',
        ]);
        $output = $parser->render();

        $this->assertSame('A|B', $output);
    }

    public function testSilentEvalSuppressesOutput()
    {
        $parser = new Parser('{=foo silent=true}');
        $output = $parser->render();

        $this->assertSame('', $output);
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

    public function testImportMakesAssignedValuesVisibleToLaterSiblingEvalInSamePass()
    {
        $dir = $this->createTempDir('import_same_pass');
        $importPath = $dir . DIRECTORY_SEPARATOR . 'vars.vibe';
        file_put_contents($importPath, '{#let bookBlueprint=\'{"title":"Guide"}\'}');

        FunctionRegistry::register(new class implements IToolFunction {
            public function name(): string
            {
                return 'captureValue';
            }

            public function execute(array $args): mixed
            {
                return $args[0] ?? null;
            }
        });

        $parser = new Parser("@import('vars.vibe'){=captureValue(bookBlueprint) -> decodedBlueprint}");
        $parser->setIncludePaths(['includes' => $dir]);
        $parser->render();

        $this->assertSame('{"title":"Guide"}', $parser->root()->getScopeVariable('bookBlueprint'));
        $this->assertSame('{"title":"Guide"}', $parser->root()->getScopeVariable('decodedBlueprint'));
    }

    public function testImportMakesEvalAssignedValuesVisibleToLaterSiblingEvalInSamePass()
    {
        $dir = $this->createTempDir('import_eval_same_pass');
        $importPath = $dir . DIRECTORY_SEPARATOR . 'vars.vibe';
        file_put_contents($importPath, '{=bookBlueprint}');

        FunctionRegistry::register(new class implements IToolFunction {
            public function name(): string
            {
                return 'captureGenerated';
            }

            public function execute(array $args): mixed
            {
                return $args[0] ?? null;
            }
        });

        $parser = new Parser("@import('vars.vibe'){=captureGenerated(bookBlueprint) -> copiedBlueprint}");
        $parser->setIncludePaths(['includes' => $dir]);
        $parser->render();

        $this->assertSame('generated', $parser->root()->getScopeVariable('bookBlueprint'));
        $this->assertSame('generated', $parser->root()->getScopeVariable('copiedBlueprint'));
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
