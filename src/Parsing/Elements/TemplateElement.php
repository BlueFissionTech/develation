<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Block;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Data\FileSystem;
use BlueFission\DevElation as Dev;

class TemplateElement extends Element implements IRenderableElement
{
    protected array $sections = [];
    protected array $outputs = [];

    public function addSection(string $name, Element $section): void
    {
        $this->sections[$name] = $section;
    }

    public function getSections(): array
    {
        return $this->sections;
    }

    public function getSection(string $name): string
    {
        return $this->sections[$name]?->getContent() ?? '';
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
        Dev::do('_before', [$this]);
        $templatePath = $this->getAttribute('name');

        if (!$templatePath) return '';
        $templatePath = Dev::apply('_in', $templatePath);

        // Register this template on the parent; actual output is deferred to later passes.
        $this->parent->setTemplate($this);

        $directory = $this->includePaths['templates'] ??
        $this->includePaths[0] ??
        null;

        $directory = $directory ? $directory . DIRECTORY_SEPARATOR : '';

        $fs = new FileSystem();
        $file = $fs->open($directory . $templatePath);
        $this->raw = Dev::apply('_in', $file->read()->contents() ?? '');

        $this->block->setContent($this->raw);

        // Defer output so post-processing can evaluate sections and outputs.
        $output = '';
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function getDescription(): string
    {
        $path = $this->getAttribute('name');

        $descriptionString = sprintf('Load a template from "%s"', $path);

        $this->description = $descriptionString;

        return $this->description;
    }
}
