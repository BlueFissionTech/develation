<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Block;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Data\FileSystem;

class ModElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        $modulePath = $this->getAttribute('name');

        if (!$modulePath) return '';

        $directory = $this->includePaths['modules'] ??
        $this->includePaths[1] ??
        $this->includePaths[0] ??
        null;

        $directory = $directory ? $directory . DIRECTORY_SEPARATOR : '';

        $fs = new FileSystem();
        $file = $fs->open($directory . $modulePath);
        $this->raw = $file->read()->contents() ?? '';

        $this->block->setContent($this->raw);

        return parent::render();
    }
}