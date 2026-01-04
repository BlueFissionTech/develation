<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Block;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Data\FileSystem;
use BlueFission\DevElation as Dev;

class IncludeElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        Dev::do('_before', [$this]);
        $modulePath = $this->getAttribute('name');

        if (!$modulePath) return '';
        $modulePath = Dev::apply('_in', $modulePath);

        $directory = $this->includePaths['modules'] ??
        $this->includePaths[1] ??
        $this->includePaths[0] ??
        null;

        $directory = $directory ? $directory . DIRECTORY_SEPARATOR : '';

        $fs = new FileSystem();
        $file = $fs->open($directory . $modulePath);
        $this->raw = Dev::apply('_in', $file->read()->contents() ?? '');

        $this->block->setContent($this->raw);

        $output = parent::render();
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function getDescription(): string
    {
        $path = $this->getAttribute('name');

        $descriptionString = sprintf('Include file from path `%s`', $path);

        $this->description = $descriptionString;

        return $this->description;
    }
}
