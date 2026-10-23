<?php

namespace App\Services;

use App\Models\PolicyRequest;

class PolicyStateMachine
{
    /**
     * Allowed transitions map.
     */
    protected const TRANSITIONS = [
        'RECEIVED' => ['VALIDATED', 'FAILED'],
        'VALIDATED' => ['SUBMITTED', 'FAILED'],
        'SUBMITTED' => ['PENDING_CONFIRMATION', 'FAILED', 'NEEDS_RECONCILIATION'],
        'PENDING_CONFIRMATION' => ['ISSUED', 'FAILED', 'NEEDS_RECONCILIATION'],
        // terminal states - no outgoing transitions except maybe retry from NEEDS_RECONCILIATION
        'ISSUED' => [],
        'FAILED' => ['SUBMITTED', 'NEEDS_RECONCILIATION'],
        'NEEDS_RECONCILIATION' => ['PENDING_CONFIRMATION', 'FAILED', 'ISSUED'],
    ];

    /**
     * Transition the policy to a new state if allowed.
     */
    public function transition(PolicyRequest $policy, string $newState): bool
    {
        $current = $policy->state;
        if (in_array($newState, self::TRANSITIONS[$current] ?? [])) {
            $policy->state = $newState;
            $policy->save();
            // Optionally create a history record (omitted for brevity)
            return true;
        }
        return false;
    }
}
?>
