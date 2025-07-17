<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\HTML\Template;
use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;

class ModElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        $file = $this->getAttribute('name');

        if (!$file) return '';

        $directory = $this->includePaths['modules'] ??
        $this->includePaths[1] ??
        $this->includePaths[0] ??
        null;

        $directory = $directory ? $directory . DIRECTORY_SEPARATOR : '';

        $fs = new FileSystem();
        $modFile = $fs->open($directory . $file);
        $content = $modFile->read()->contents();

        $block = new Block($content);
        $block->field('vars', $this->block->allVars());
        $block->parse();

        return $block->process();
    }
}