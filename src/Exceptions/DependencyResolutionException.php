<?php

namespace BlueFission\Exceptions;

/**
 * Reports a dependency that the active application cannot instantiate.
 */
class DependencyResolutionException extends \RuntimeException
{
    public function __construct(
        private string $dependency,
        private string $phase,
        private string $application,
        private string $resolvedClass
    ) {
        parent::__construct(
            "Unable to resolve dependency '{$dependency}' during lifecycle phase "
            . "'{$phase}' for application '{$application}': '{$resolvedClass}' is not instantiable."
        );
    }

    public function dependency(): string
    {
        return $this->dependency;
    }

    public function phase(): string
    {
        return $this->phase;
    }

    public function application(): string
    {
        return $this->application;
    }

    public function resolvedClass(): string
    {
        return $this->resolvedClass;
    }

    /**
     * Return machine-readable diagnostic context for hosts and logs.
     *
     * @return array{dependency: string, phase: string, application: string, resolvedClass: string}
     */
    public function context(): array
    {
        return [
            'dependency' => $this->dependency,
            'phase' => $this->phase,
            'application' => $this->application,
            'resolvedClass' => $this->resolvedClass,
        ];
    }
}
