<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Elements\TemplateElement;
use BlueFission\Parsing\Contracts\IRenderableElement;

class SectionElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        $sectionName = $this->getAttribute('name');
        $parent = $this->getParent();
        $templates = [];
        $template = null;

        if ($sectionName) {
            while ($parent && $template === null) {
                $templates = (new Collection($parent->children())->filter(function($child) {
                    return $child instanceof TemplateElement;
                }))->toArray();            

                if (count($templates)) {
                    $template = end($templates);
                } else {
                    $parent = $parent->getParent();
                }
            }
        }

        if ($template) {
            $templates->addSection($sectionName, $this);
        }
        
        return '';
    }

    public function build(): string
    {
        return parent::render();
    }
}