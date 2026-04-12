<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\DevElation as Dev;
use BlueFission\Prototypes\Contracts\Causal;

trait IsCausal
{
    public function addCause(mixed $cause, array $meta = []): static
    {
        $causes = Arr::toArray($this->prototypeGet('causes', []));
        $causes[] = $this->normalizeCausalRecord($cause, $meta);

        return $this->prototypeSet('causes', $causes, 'prototypes.causal.cause_added');
    }

    public function addEffect(mixed $effect, array $meta = []): static
    {
        $effects = Arr::toArray($this->prototypeGet('effects', []));
        $effects[] = $this->normalizeCausalRecord($effect, $meta);

        return $this->prototypeSet('effects', $effects, 'prototypes.causal.effect_added');
    }

    public function because(mixed $cause, mixed $effect = null, array $meta = []): static
    {
        $this->addCause($cause, $meta);

        if ($effect !== null) {
            $this->addEffect($effect, $meta);
        }

        return $this;
    }

    public function causes(): array
    {
        return Arr::toArray($this->prototypeGet('causes', []));
    }

    public function effects(): array
    {
        return Arr::toArray($this->prototypeGet('effects', []));
    }

    public function inferCauses(array $context = []): array
    {
        return $this->prototypeInferCausalRecords($this->causes(), $context, 'prototypes.causal.causes_inferred');
    }

    public function inferEffects(array $context = []): array
    {
        return $this->prototypeInferCausalRecords($this->effects(), $context, 'prototypes.causal.effects_inferred');
    }

    protected function normalizeCausalRecord(mixed $record, array $meta = []): array
    {
        if (is_callable($record)) {
            return $this->prototypeMergeArrays([
                'name' => 'causal_' . uniqid(),
                'resolver' => $record,
                'weight' => 1.0,
                'confidence' => 1.0,
                'conditions' => [],
            ], $meta);
        }

        if (is_array($record)) {
            return $this->prototypeMergeArrays([
                'name' => (string) ($record['name'] ?? 'causal_' . uniqid()),
                'weight' => (float) ($record['weight'] ?? 1.0),
                'confidence' => (float) ($record['confidence'] ?? 1.0),
                'conditions' => Arr::toArray($record['conditions'] ?? []),
            ], $record, $meta);
        }

        return $this->prototypeMergeArrays([
            'name' => (string) $record,
            'weight' => 1.0,
            'confidence' => 1.0,
            'conditions' => [],
        ], $meta);
    }

    protected function prototypeInferCausalRecords(array $records, array $context, string $hook): array
    {
        $records = Dev::apply('prototypes.causal.infer.in', $records);
        $matches = [];

        foreach ($records as $record) {
            if (isset($record['resolver']) && is_callable($record['resolver'])) {
                $result = call_user_func($record['resolver'], $context, $this, $record);
                if (is_array($result)) {
                    $matches[] = $this->prototypeMergeArrays($record, $result);
                    continue;
                }
                if ($result) {
                    $matches[] = $record;
                }
                continue;
            }

            $conditions = Arr::toArray($record['conditions'] ?? []);
            $valid = true;

            if (!empty($conditions) && method_exists($this, 'prototypeEvaluateConditionRecord')) {
                foreach ($conditions as $condition) {
                    if (!$this->prototypeEvaluateConditionRecord($condition, $context)) {
                        $valid = false;
                        break;
                    }
                }
            }

            if ($valid) {
                $matches[] = $record;
            }
        }

        $matches = $this->prototypeSortByWeight($matches);
        $this->prototypeSignal($hook, ['context' => $context, 'matches' => $matches, 'object' => $this]);

        return $matches;
    }
}
