<?php

namespace App\Services;

use App\Models\PolicyRequest;
use App\Models\IssuanceAttempt;
use Illuminate\Support\Str;

class MockInsurerService
{
    /**
     * Simulate submission to the insurer based on configured scenario.
     *
     * @param  PolicyRequest  $policy
     * @return array  Contains 'status' and optional 'provider_reference', 'policy_number'
     */
    public function submit(PolicyRequest $policy): array
    {
        $scenario = config('mock_insurer.scenario', 'SUCCESS');

        // Generate deterministic identifiers based on policy id for reproducibility
        $providerRef = 'PRV' . str_pad($policy->id, 6, '0', STR_PAD_LEFT);
        $policyNumber = 'POL' . strtoupper(Str::random(8));

        switch ($scenario) {
            case 'SUCCESS':
                $this->recordAttempt($policy, $providerRef, 'SUCCESS', ['policy_number' => $policyNumber]);
                return ['status' => 'SUCCESS', 'provider_reference' => $providerRef, 'policy_number' => $policyNumber];
            case 'TEMPORARY_FAILURE':
                $this->recordAttempt($policy, null, 'FAILURE', ['error' => 'Transient network issue']);
                return ['status' => 'FAILURE'];
            case 'TIMEOUT_AFTER_ISSUE':
                // Provider has issued, but client times out.
                $this->recordAttempt($policy, $providerRef, 'TIMEOUT', ['policy_number' => $policyNumber]);
                return ['status' => 'TIMEOUT', 'provider_reference' => $providerRef, 'policy_number' => $policyNumber];
            case 'DELAYED_CALLBACK':
                $this->recordAttempt($policy, null, 'PENDING', []);
                // No immediate provider data; later callback will deliver SUCCESS.
                return ['status' => 'PENDING'];
            case 'DUPLICATE_CALLBACK':
                // Behave like SUCCESS but will be sent multiple times.
                $this->recordAttempt($policy, $providerRef, 'SUCCESS', ['policy_number' => $policyNumber]);
                return ['status' => 'SUCCESS', 'provider_reference' => $providerRef, 'policy_number' => $policyNumber];
            default:
                $this->recordAttempt($policy, null, 'FAILURE', ['error' => 'Unknown scenario']);
                return ['status' => 'FAILURE'];
        }
    }

    protected function recordAttempt(PolicyRequest $policy, ?string $providerRef, string $status, array $payload): void
    {
        IssuanceAttempt::create([
            'policy_request_id' => $policy->id,
            'provider_reference' => $providerRef,
            'status' => $status,
            'response_payload' => $payload,
        ]);
    }
}
?>
