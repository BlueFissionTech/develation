<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Data\FileSystem;
use BlueFission\Parsing\Contracts\IExecutableElement;

class ImportElement extends Element implements IExecutableElement
{
    public function execute(): mixed
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

        $this->block->setContent($content);
        $this->block->parse();
        $this->block->process();

        return '';
    }

    public function render(): string
    {
        return '';
    }

    public function getDescription(): string
    {
        $path = $this->getAttribute('name');

        $descriptionString = sprintf('Import data from path `%s`', $path);

        $this->description = $descriptionString;

        return $this->description;
    }
}