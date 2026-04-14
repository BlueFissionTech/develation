<?php

namespace BlueFission\Prototypes;

/**
 * Entity
 *
 * Reactionary artifact that can respond to events, stimuli, conditions, and
 * changes in domain context without implying independent strategic control.
 */
trait Entity
{
    /**
     * Get or assign whether the entity is considered reactive.
     *
     * @param bool|null $reactive
     * @return mixed
     */
    public function reactive(?bool $reactive = null): mixed
    {
        $this->prototypeBoot('entity');
        $this->kind('entity');

        if ($reactive === null) {
            return (bool) $this->prototypeGet('reactive', true);
        }

        return $this->prototypeSet('reactive', $reactive, 'prototypes.entity.reactivity_set');
    }

    /**
     * Record a reaction event for the entity and emit a prototype signal.
     *
     * @param mixed $stimulus
     * @param array<string, mixed> $meta
     * @return static
     */
    public function react(mixed $stimulus, array $meta = []): static
    {
        $this->record('reaction', [
            'stimulus' => $this->prototypeSnapshotValue($stimulus),
            'meta' => $this->prototypeSnapshotValue($meta),
        ]);

        $this->prototypeSignal('prototypes.entity.reacted', [
            'stimulus' => $this->prototypeSnapshotValue($stimulus),
            'meta' => $this->prototypeSnapshotValue($meta),
            'object' => $this,
        ]);

        return $this;
    }
}
