<?php

namespace BlueFission\Prototypes;

trait Entity
{
    public function reactive(?bool $reactive = null): mixed
    {
        $this->prototypeBoot('entity');
        $this->kind('entity');

        if ($reactive === null) {
            return (bool) $this->prototypeGet('reactive', true);
        }

        return $this->prototypeSet('reactive', $reactive, 'prototypes.entity.reactivity_set');
    }

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
