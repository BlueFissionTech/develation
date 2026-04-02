<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Registry\ValidatorRegistry;
use BlueFission\DevElation as Dev;

class FormatElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        Dev::do('_before', [$this]);

        $this->parse();

        $validatorName = $this->getAttribute('validator') ?? 'notEmpty';
        $retries = $this->getAttribute('retries');
        $retries = is_numeric($retries) ? (int)$retries : 1;

        if ($retries < 1) {
            $retries = 1;
        }

        $validator = ValidatorRegistry::get($validatorName);
        $output = '';

        for ($attempt = 0; $attempt < $retries; $attempt++) {
            $this->block->setContent($this->getRaw());
            $this->block->process();
            $output = $this->getContent();
            $this->setContent($output);

            if (!$validator) {
                break;
            }

            try {
                if ($validator->validate($this)) {
                    break;
                }
            } catch (\Throwable $e) {
                if ($attempt === $retries - 1) {
                    $output = '';
                }
            }
        }

        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);

        return $output;
    }

    public function getDescription(): string
    {
        $type = $this->getAttribute('type') ?? 'text';
        $validator = $this->getAttribute('validator') ?? 'notEmpty';
        $retries = $this->getAttribute('retries') ?? 1;

        $descriptionString = sprintf(
            'Format block enforcing type "%s" using validator "%s" with up to %d retries.',
            $type,
            $validator,
            (int)$retries
        );

        $this->description = $descriptionString;

        return $this->description;
    }
}
