<?php

namespace BlueFission\Prototypes;

use BlueFission\Val;

/**
 * Artifact
 *
 * Represents a blueprint-realized thing with substance or material presence.
 * Artifacts are still generic, but they carry the notion of "thingness" that
 * downstream libraries can refine into entities, assets, or world objects.
 */
trait Artifact
{
    /**
     * Get or assign the originating blueprint for this artifact.
     *
     * @param mixed $blueprint
     * @return mixed
     */
    public function blueprint(mixed $blueprint = null): mixed
    {
        $this->prototypeBoot('artifact');
        $this->kind('artifact');

        if (Val::isNull($blueprint)) {
            return $this->prototypeGet('blueprint');
        }

        return $this->prototypeSet('blueprint', $this->prototypeSnapshotValue($blueprint), 'prototypes.artifact.blueprint_set');
    }

    /**
     * Get or assign the artifact's substance payload.
     *
     * @param mixed $substance
     * @return mixed
     */
    public function substance(mixed $substance = null): mixed
    {
        if (Val::isNull($substance)) {
            return $this->prototypeGet('substance');
        }

        return $this->prototypeSet('substance', $this->prototypeSnapshotValue($substance), 'prototypes.artifact.substance_set');
    }

    /**
     * Get or assign a materiality descriptor for the artifact.
     *
     * @param mixed $materiality
     * @return mixed
     */
    public function materiality(mixed $materiality = null): mixed
    {
        if (Val::isNull($materiality)) {
            return $this->prototypeGet('materiality', 'substantial');
        }

        return $this->prototypeSet('materiality', $this->prototypeSnapshotValue($materiality), 'prototypes.artifact.materiality_set');
    }
}
