<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Collections\Collection;
use BlueFission\Parsing\Elements\TemplateElement;
use BlueFission\Parsing\Contracts\IRenderableElement;

class SectionElement extends Element implements IRenderableElement
{
    // public function render(): string
    // {
    //     $output = $this->getContent();

    //     $sectionName = $this->getAttribute('name');
    //     $template = $this->getTemplate();
    //     die($sectionName."!!");

    //     if (!$sectionName || !$template) return $output;

    //     $output = $template->getSection($sectionName);

    //     $template->addOutput($sectionName, $output);

    //     return $output;
    // }

    public function getTemplate(): ?Element
    {
        if ($this->template) return $this->template;

        // Walk ancestors to find the nearest template context.
        $parent = $this->getParent();
        $template = null;
        $templates = [];

        while ($parent && $template === null) {
            $templates = ((new Collection($parent->children()))->filter(function($child) {
                return $child instanceof TemplateElement;
            }))->toArray();

            if (count($templates)) {
                $template = end($templates);
            } else {
                $parent = $parent->getParent();
            }
        }

        $this->template = $template;

        return $template;
    }

    public function build(): string
    {
        $sectionName = $this->getAttribute('name');

        if ($sectionName) {
            $template = null;
            $template = $this->getTemplate();

            if ($template) {
                // Register the section so templates can map outputs later.
                $template->addSection($sectionName, $this);
            }
        }

        return $this->getContent();
        // return $this->render();
    }

    public function getDescription(): string
    {
        $name = $this->getAttribute('name');

        $descriptionString = sprintf('Designate a new content section "%s"', $name);

        $this->description = $descriptionString;

        return $this->description;
    }
}
