<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Data\FileSystem;
use BlueFission\Parsing\Contracts\IExecutableElement;
use BlueFission\DevElation as Dev;

class ImportElement extends Element implements IExecutableElement
{
    public function execute(): mixed
    {
        Dev::do('_before', [$this]);
        $file = $this->getAttribute('name');
        if (!$file) return '';
        $file = Dev::apply('_in', $file);

        $directory = $this->includePaths['includes'] ??
        $this->includePaths[1] ??
        $this->includePaths[0] ??
        null;

        $directory = $directory ? $directory . DIRECTORY_SEPARATOR : '';

        $fs = new FileSystem();
        $importFile = $fs->open($directory . $file);
        $content = $importFile->read()->contents();
        $content = Dev::apply('_in', $content);

        $this->block->setContent($content);
        $this->block->parse();
        $this->block->process();

        Dev::do('_after', [$file, $this]);
        return '';
    }

    public function render(): string
    {
        $output = '';
        $output = Dev::apply('_out', $output);
        return $output;
    }

    public function getDescription(): string
    {
        $path = $this->getAttribute('name');

        $descriptionString = sprintf('Import data from path `%s`', $path);

        $this->description = $descriptionString;

        return $this->description;
    }
}
