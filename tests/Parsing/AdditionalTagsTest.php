<?php
namespace BlueFission\Tests\Parsing;

use BlueFission\Parsing\Parser;
use BlueFission\Parsing\Registry\TagRegistry;

class AdditionalTagsTest extends ParsingTestCase
{
    protected function setUp(): void
    {
        $this->registerParsingDefaults();
    }

    public function testAwaitRendersInnerContent()
    {
        $template = '{#await event="ready"}Done{/await}';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('Done', $output);
    }

    public function testWhileFalseConditionDoesNotLoop()
    {
        $template = 'A{#while condition=false}Loop{/while}B';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('AB', $output);
    }

    public function testMacroTagIsRegistered()
    {
        $definition = TagRegistry::get('macro');
        $this->assertNotNull($definition);
        $this->assertSame('macro', $definition->name);
    }

    public function testInvokeResolvesAndRendersMacro()
    {
        $template = "@macro('greet')Hello {\$name}!@endmacro @invoke('greet' name=World)";
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertStringContainsString('Hello World!', $output);
    }
}
