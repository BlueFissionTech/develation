<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\Val;

/**
 * Position
 *
 * Multi-dimensional locator substrate that can describe physical, conceptual,
 * or workflow-relative placement without hardcoding a specific coordinate
 * system.
 */
trait Position
{
    /**
     * Define metadata for one named position dimension.
     *
     * @param string $name
     * @param array<string, mixed> $descriptor
     * @return static
     */
    public function defineDimension(string $name, array $descriptor = []): static
    {
        $dimensions = Arr::toArray($this->prototypeGet('dimensions', []));
        $dimensions[$name] = $this->prototypeMergeArrays([
            'label' => $name,
            'kind' => 'relative',
            'absolute' => false,
            'unit' => null,
            'default' => null,
            'comparable' => true,
            'directionality' => 'bidirectional',
        ], $descriptor);

        return $this->prototypeSet('dimensions', $dimensions, 'prototypes.position.dimension_defined');
    }

    /**
     * Return all defined position dimensions.
     *
     * @return array<string, array<string, mixed>>
     */
    public function dimensions(): array
    {
        return Arr::toArray($this->prototypeGet('dimensions', []));
    }

    /**
     * Get or assign one coordinate value.
     *
     * @param string $dimension
     * @param mixed $value
     * @return mixed
     */
    public function coordinate(string $dimension, mixed $value = null): mixed
    {
        $coordinates = Arr::toArray($this->prototypeGet('coordinates', []));

        if (func_num_args() === 1) {
            return $coordinates[$dimension] ?? null;
        }

        $coordinates[$dimension] = $this->prototypeSnapshotValue($value);

        return $this->prototypeSet('coordinates', $coordinates, 'prototypes.position.coordinate_changed');
    }

    /**
     * Return the complete coordinate bag.
     *
     * @return array<string, mixed>
     */
    public function coordinates(): array
    {
        return Arr::toArray($this->prototypeGet('coordinates', []));
    }

    /**
     * Get or assign the frame of reference used by the position.
     *
     * @param mixed $frame
     * @return mixed
     */
    public function frame(mixed $frame = null): mixed
    {
        if (Val::isNull($frame)) {
            return $this->prototypeGet('frame');
        }

        return $this->prototypeSet('frame', $this->prototypeSnapshotValue($frame), 'prototypes.position.frame_set');
    }

    /**
     * Get or assign an anchor value for relative positioning.
     *
     * @param mixed $anchor
     * @return mixed
     */
    public function anchor(mixed $anchor = null): mixed
    {
        if (Val::isNull($anchor)) {
            return $this->prototypeGet('anchor');
        }

        return $this->prototypeSet('anchor', $this->prototypeSnapshotValue($anchor), 'prototypes.position.anchor_set');
    }

    /**
     * Translate current coordinates by a delta payload.
     *
     * @param array<string, mixed> $delta
     * @return static
     */
    public function translate(array $delta): static
    {
        $coordinates = $this->coordinates();

        foreach ($delta as $dimension => $amount) {
            $current = $coordinates[$dimension] ?? 0;
            if (is_numeric($current) && is_numeric($amount)) {
                $coordinates[$dimension] = $current + $amount;
            } else {
                $coordinates[$dimension] = $amount;
            }
        }

        $this->prototypeSet('coordinates', $coordinates, 'prototypes.position.translated');
        $this->position($coordinates);

        return $this;
    }

    /**
     * Calculate numeric distance or per-dimension deltas to another position.
     *
     * @param mixed $other
     * @param array<int, string>|null $dimensions
     * @return float|array<string, mixed>|null
     */
    public function distanceTo(mixed $other, ?array $dimensions = null): float|array|null
    {
        $otherCoordinates = [];

        if (is_object($other) && method_exists($other, 'coordinates')) {
            $otherCoordinates = $other->coordinates();
        } elseif (is_array($other)) {
            $otherCoordinates = $other;
        } else {
            return null;
        }

        $dimensions = $dimensions ?? array_keys($this->prototypeMergeArrays($this->coordinates(), $otherCoordinates));
        $squared = 0.0;
        $deltas = [];
        $numeric = true;

        foreach ($dimensions as $dimension) {
            $left = $this->coordinate($dimension);
            $right = $otherCoordinates[$dimension] ?? null;

            if (is_numeric($left) && is_numeric($right)) {
                $diff = (float) $right - (float) $left;
                $deltas[$dimension] = $diff;
                $squared += ($diff * $diff);
                continue;
            }

            $numeric = false;
            $deltas[$dimension] = [
                'from' => $left,
                'to' => $right,
            ];
        }

        return $numeric ? sqrt($squared) : $deltas;
    }
}
