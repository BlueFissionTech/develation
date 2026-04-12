<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\Val;

trait Agent
{
    public function role(?string $role = null): mixed
    {
        $this->prototypeBoot('agent');
        $this->kind('agent');

        if (Val::isNull($role)) {
            return (string) $this->prototypeGet('role', '');
        }

        return $this->prototypeSet('role', Str::trim($role), 'prototypes.agent.role_set');
    }

    public function scope(?string $scope = null): mixed
    {
        if (Val::isNull($scope)) {
            return (string) $this->prototypeGet('scope', '');
        }

        return $this->prototypeSet('scope', Str::trim($scope), 'prototypes.agent.scope_set');
    }

    public function awareness(?string $awareness = null): mixed
    {
        if (Val::isNull($awareness)) {
            return (string) $this->prototypeGet('awareness', '');
        }

        return $this->prototypeSet('awareness', Str::trim($awareness), 'prototypes.agent.awareness_set');
    }

    public function efficacy(?string $efficacy = null): mixed
    {
        if (Val::isNull($efficacy)) {
            return (string) $this->prototypeGet('efficacy', '');
        }

        return $this->prototypeSet('efficacy', Str::trim($efficacy), 'prototypes.agent.efficacy_set');
    }

    public function autonomy(?string $autonomy = null): mixed
    {
        if (Val::isNull($autonomy)) {
            return (string) $this->prototypeGet('autonomy', 'autonomous');
        }

        return $this->prototypeSet('autonomy', Str::trim($autonomy), 'prototypes.agent.autonomy_set');
    }

    public function control(?string $control = null): mixed
    {
        if (Val::isNull($control)) {
            return (string) $this->prototypeGet('control', 'self');
        }

        return $this->prototypeSet('control', Str::trim($control), 'prototypes.agent.control_set');
    }

    public function addGoal(mixed $goal): static
    {
        return $this->prototypeAppend('goals', $goal, false, 'prototypes.agent.goal_added');
    }

    public function goals(): array
    {
        return Arr::toArray($this->prototypeGet('goals', []));
    }

    public function addCriterion(string $name, int|float $weight = 1, string $concern = ''): static
    {
        return $this->prototypeAppend('criteria', [
            'name' => $name,
            'weight' => $weight,
            'concern' => $concern,
        ], false, 'prototypes.agent.criterion_added');
    }

    public function criteria(): array
    {
        return Arr::toArray($this->prototypeGet('criteria', []));
    }

    public function addStrategy(string $name, int|float $weight = 1, string $concern = ''): static
    {
        return $this->prototypeAppend('strategies', [
            'name' => $name,
            'weight' => $weight,
            'concern' => $concern,
        ], false, 'prototypes.agent.strategy_added');
    }

    public function strategies(): array
    {
        return Arr::toArray($this->prototypeGet('strategies', []));
    }

    public function decide(mixed $decisionResult): static
    {
        $decision = $this->prototypeSnapshotValue($decisionResult);
        $this->prototypeSet('lastDecision', $decision, 'prototypes.agent.decision_set');
        $this->record('decision', ['decision' => $decision]);

        return $this;
    }
}
