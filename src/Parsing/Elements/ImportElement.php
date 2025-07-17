<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Data\FileSystem;
use BlueFission\Parsing\Interfaces\IExecutableElement;

class ImportElement extends Element implements IExecutableElement
{
    public function execute(): string
    {
        $file = $this->getAttribute('name');
        if (!$file) return '';

        $directory = $this->includePaths['includes'] ??
        $this->includePaths[1] ??
        $this->includePaths[0] ??
        null;

        $directory = $directory ? $directory . DIRECTORY_SEPARATOR : '';

        $fs = new FileSystem();
        $importFile = $fs->open($directory . $file);
        $content = $importFile->read()->contents();

        $block = new Block($content);
        $block->field('vars', $this->block->allVars());
        $block->process(); // preload variables, but discard render output

        return '';
    }
}