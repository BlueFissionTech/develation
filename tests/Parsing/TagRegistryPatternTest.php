<?php
namespace BlueFission\Tests\Parsing;

use BlueFission\Parsing\Parser;
use BlueFission\Parsing\TagDefinition;
use BlueFission\Parsing\Registry\TagRegistry;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Elements\CommentElement;

class TagRegistryPatternTest extends ParsingTestCase
{
    protected function setUp(): void
    {
        $this->registerParsingDefaults();
    }

    public function testUnifiedPatternSupportsColonTags()
    {
        TagRegistry::register(new TagDefinition(
            name: 'mix:construct',
            pattern: '{open}\#mix:construct(.*?)?{close}(.*?){open}\/mix:construct{close}',
            attributes: ['*'],
            interface: IRenderableElement::class,
            class: CommentElement::class
        ));

        $template = '{#mix:construct}Hello{/mix:construct}';
        $parser = new Parser($template);
        $output = $parser->render();

        $this->assertSame('', $output);
    }
}
