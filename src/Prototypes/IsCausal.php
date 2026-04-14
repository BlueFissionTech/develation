<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\DevElation as Dev;
use BlueFission\Prototypes\Contracts\Causal;

/**
 * IsCausal
 *
 * Records candidate causes and effects and exposes deterministic filtering
 * hooks so more advanced inference systems can consume the same metadata later.
 */
trait IsCausal
{
    /**
     * Add one possible cause record.
     *
     * @param mixed $cause
     * @param array<string, mixed> $meta
     * @return static
     */
    public function addCause(mixed $cause, array $meta = []): static
    {
        $causes = Arr::toArray($this->prototypeGet('causes', []));
        $causes[] = $this->normalizeCausalRecord($cause, $meta);

        return $this->prototypeSet('causes', $causes, 'prototypes.causal.cause_added');
    }

    /**
     * Add one possible effect record.
     *
     * @param mixed $effect
     * @param array<string, mixed> $meta
     * @return static
     */
    public function addEffect(mixed $effect, array $meta = []): static
    {
        $effects = Arr::toArray($this->prototypeGet('effects', []));
        $effects[] = $this->normalizeCausalRecord($effect, $meta);

        return $this->prototypeSet('effects', $effects, 'prototypes.causal.effect_added');
    }

    /**
     * Link one cause and optional effect in a single call.
     *
     * @param mixed $cause
     * @param mixed $effect
     * @param array<string, mixed> $meta
     * @return static
     */
    public function because(mixed $cause, mixed $effect = null, array $meta = []): static
    {
        $this->addCause($cause, $meta);

        if ($effect !== null) {
            $this->addEffect($effect, $meta);
        }

        return $this;
    }

    /**
     * Return all registered cause records.
     *
     * @return array<int, array<string, mixed>>
     */
    public function causes(): array
    {
        return Arr::toArray($this->prototypeGet('causes', []));
    }

    /**
     * Return all registered effect records.
     *
     * @return array<int, array<string, mixed>>
     */
    public function effects(): array
    {
        return Arr::toArray($this->prototypeGet('effects', []));
    }

    /**
     * Filter and rank candidate causes against the supplied context.
     *
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public function inferCauses(array $context = []): array
    {
        return $this->prototypeInferCausalRecords($this->causes(), $context, 'prototypes.causal.causes_inferred');
    }

    /**
     * Filter and rank candidate effects against the supplied context.
     *
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public function inferEffects(array $context = []): array
    {
        return $this->prototypeInferCausalRecords($this->effects(), $context, 'prototypes.causal.effects_inferred');
    }

    /**
     * Normalize shorthand causal values or resolvers into a standard record.
     *
     * @param mixed $record
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
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

    /**
     * Apply condition-aware filtering and weight sorting to causal candidates.
     *
     * @param array<int, array<string, mixed>> $records
     * @param array<string, mixed> $context
     * @param string $hook
     * @return array<int, array<string, mixed>>
     */
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
