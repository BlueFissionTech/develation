<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Block;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Data\FileSystem;

class TemplateElement extends Element implements IRenderableElement
{
    protected array $outputs = [];

    public function render(): string
    {
        $templatePath = $this->getAttribute('name');

        if (!$templatePath) return '';

        $this->parent->setTemplate($this);

        $directory = $this->includePaths['templates'] ??
        $this->includePaths[0] ??
        null;

        $directory = $directory ? $directory . DIRECTORY_SEPARATOR : '';

        $fs = new FileSystem();
        $file = $fs->open($directory . $templatePath);
        $this->raw = $file->read()->contents() ?? '';

        $this->block->setContent($this->raw);

        return '';
    }

    public function addOutput(string $name, string $output): void
    {
        $this->outputs[$name] = $output;
    }

    public function findOutput(string $name): string
    {
        return $this->outputs[$name] ?? '';
    }

    public function build(): string
    {
        return parent::render();
    }
}