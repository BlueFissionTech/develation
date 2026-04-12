<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\Val;

trait Position
{
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

    public function dimensions(): array
    {
        return Arr::toArray($this->prototypeGet('dimensions', []));
    }

    public function coordinate(string $dimension, mixed $value = null): mixed
    {
        $coordinates = Arr::toArray($this->prototypeGet('coordinates', []));

        if (func_num_args() === 1) {
            return $coordinates[$dimension] ?? null;
        }

        $coordinates[$dimension] = $this->prototypeSnapshotValue($value);

        return $this->prototypeSet('coordinates', $coordinates, 'prototypes.position.coordinate_changed');
    }

    public function coordinates(): array
    {
        return Arr::toArray($this->prototypeGet('coordinates', []));
    }

    public function frame(mixed $frame = null): mixed
    {
        if (Val::isNull($frame)) {
            return $this->prototypeGet('frame');
        }

        return $this->prototypeSet('frame', $this->prototypeSnapshotValue($frame), 'prototypes.position.frame_set');
    }

    public function anchor(mixed $anchor = null): mixed
    {
        if (Val::isNull($anchor)) {
            return $this->prototypeGet('anchor');
        }

        return $this->prototypeSet('anchor', $this->prototypeSnapshotValue($anchor), 'prototypes.position.anchor_set');
    }

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
