<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Block;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Data\FileSystem;

class IncludeElement extends Element implements IRenderableElement
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

    public function getDescription(): string
    {
        $path = $this->getAttribute('name');

        $descriptionString = sprintf('Include file from path `%s`', $path);

        $this->description = $descriptionString;

        return $this->description;
    }
}