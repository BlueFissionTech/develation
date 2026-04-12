<?php

namespace BlueFission\Prototypes;

use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\Val;

/**
 * Agent
 *
 * Autonomous or externally controlled entity substrate. Agents accumulate
 * goals, criteria, strategies, and decision history without embedding a full
 * planning or reasoning engine in Develation itself.
 */
trait Agent
{
    /**
     * Get or assign the agent's role within a system or domain.
     *
     * @param string|null $role
     * @return mixed
     */
    public function role(?string $role = null): mixed
    {
        $this->prototypeBoot('agent');
        $this->kind('agent');

        if (Val::isNull($role)) {
            return (string) $this->prototypeGet('role', '');
        }

        return $this->prototypeSet('role', Str::trim($role), 'prototypes.agent.role_set');
    }

    /**
     * Get or assign the operating scope of the agent.
     *
     * @param string|null $scope
     * @return mixed
     */
    public function scope(?string $scope = null): mixed
    {
        if (Val::isNull($scope)) {
            return (string) $this->prototypeGet('scope', '');
        }

        return $this->prototypeSet('scope', Str::trim($scope), 'prototypes.agent.scope_set');
    }

    /**
     * Get or assign a qualitative awareness descriptor.
     *
     * @param string|null $awareness
     * @return mixed
     */
    public function awareness(?string $awareness = null): mixed
    {
        if (Val::isNull($awareness)) {
            return (string) $this->prototypeGet('awareness', '');
        }

        return $this->prototypeSet('awareness', Str::trim($awareness), 'prototypes.agent.awareness_set');
    }

    /**
     * Get or assign a qualitative efficacy descriptor.
     *
     * @param string|null $efficacy
     * @return mixed
     */
    public function efficacy(?string $efficacy = null): mixed
    {
        if (Val::isNull($efficacy)) {
            return (string) $this->prototypeGet('efficacy', '');
        }

        return $this->prototypeSet('efficacy', Str::trim($efficacy), 'prototypes.agent.efficacy_set');
    }

    /**
     * Get or assign how autonomous the agent is considered to be.
     *
     * @param string|null $autonomy
     * @return mixed
     */
    public function autonomy(?string $autonomy = null): mixed
    {
        if (Val::isNull($autonomy)) {
            return (string) $this->prototypeGet('autonomy', 'autonomous');
        }

        return $this->prototypeSet('autonomy', Str::trim($autonomy), 'prototypes.agent.autonomy_set');
    }

    /**
     * Get or assign the controller responsible for the agent.
     *
     * @param string|null $control
     * @return mixed
     */
    public function control(?string $control = null): mixed
    {
        if (Val::isNull($control)) {
            return (string) $this->prototypeGet('control', 'self');
        }

        return $this->prototypeSet('control', Str::trim($control), 'prototypes.agent.control_set');
    }

    /**
     * Append one goal descriptor to the agent.
     *
     * @param mixed $goal
     * @return static
     */
    public function addGoal(mixed $goal): static
    {
        return $this->prototypeAppend('goals', $goal, false, 'prototypes.agent.goal_added');
    }

    /**
     * Return all registered goals.
     *
     * @return array<int, mixed>
     */
    public function goals(): array
    {
        return Arr::toArray($this->prototypeGet('goals', []));
    }

    /**
     * Add one weighted decision criterion.
     *
     * @param string $name
     * @param int|float $weight
     * @param string $concern
     * @return static
     */
    public function addCriterion(string $name, int|float $weight = 1, string $concern = ''): static
    {
        return $this->prototypeAppend('criteria', [
            'name' => $name,
            'weight' => $weight,
            'concern' => $concern,
        ], false, 'prototypes.agent.criterion_added');
    }

    /**
     * Return all weighted decision criteria.
     *
     * @return array<int, array<string, mixed>>
     */
    public function criteria(): array
    {
        return Arr::toArray($this->prototypeGet('criteria', []));
    }

    /**
     * Add one weighted strategy descriptor.
     *
     * @param string $name
     * @param int|float $weight
     * @param string $concern
     * @return static
     */
    public function addStrategy(string $name, int|float $weight = 1, string $concern = ''): static
    {
        return $this->prototypeAppend('strategies', [
            'name' => $name,
            'weight' => $weight,
            'concern' => $concern,
        ], false, 'prototypes.agent.strategy_added');
    }

    /**
     * Return all registered strategies.
     *
     * @return array<int, array<string, mixed>>
     */
    public function strategies(): array
    {
        return Arr::toArray($this->prototypeGet('strategies', []));
    }

    /**
     * Persist the most recent decision result and add it to history.
     *
     * @param mixed $decisionResult
     * @return static
     */
    public function decide(mixed $decisionResult): static
    {
        $decision = $this->prototypeSnapshotValue($decisionResult);
        $this->prototypeSet('lastDecision', $decision, 'prototypes.agent.decision_set');
        $this->record('decision', ['decision' => $decision]);

        return $this;
    }
}
