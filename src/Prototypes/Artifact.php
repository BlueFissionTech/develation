<?php

namespace BlueFission\Prototypes;

use BlueFission\Val;

trait Artifact
{
    public function blueprint(mixed $blueprint = null): mixed
    {
        $this->prototypeBoot('artifact');
        $this->kind('artifact');

        if (Val::isNull($blueprint)) {
            return $this->prototypeGet('blueprint');
        }

        return $this->prototypeSet('blueprint', $this->prototypeSnapshotValue($blueprint), 'prototypes.artifact.blueprint_set');
    }

    public function substance(mixed $substance = null): mixed
    {
        if (Val::isNull($substance)) {
            return $this->prototypeGet('substance');
        }

        return $this->prototypeSet('substance', $this->prototypeSnapshotValue($substance), 'prototypes.artifact.substance_set');
    }

    public function materiality(mixed $materiality = null): mixed
    {
        if (Val::isNull($materiality)) {
            return $this->prototypeGet('materiality', 'substantial');
        }

        return $this->prototypeSet('materiality', $this->prototypeSnapshotValue($materiality), 'prototypes.artifact.materiality_set');
    }
}
