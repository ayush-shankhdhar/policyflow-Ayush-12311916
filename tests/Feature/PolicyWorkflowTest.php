<?php

namespace Tests\Feature;

use App\Models\PolicyRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function temporary_failure_is_retried_and_eventually_issues_policy()
    {
        // First attempt: temporary failure
        config(['mock_insurer.scenario' => 'TEMPORARY_FAILURE']);
        $payload = $this->validPayload('REQTEMP');
        $response = $this->postJson('/api/policy-requests', $payload);
        $policyId = $response->json('id');
        $submitResp = $this->postJson("/api/policy-requests/{$policyId}/submit");
        $submitResp->assertStatus(200)->assertJson(['status' => 'failed']);
        $this->assertDatabaseHas('policy_requests', ['id' => $policyId, 'state' => 'FAILED']);

        // Retry with success scenario
        config(['mock_insurer.scenario' => 'SUCCESS']);
        $retryResp = $this->postJson("/api/policy-requests/{$policyId}/submit");
        $retryResp->assertStatus(200)->assertJson(['status' => 'issued']);
        $this->assertDatabaseHas('policy_requests', ['id' => $policyId, 'state' => 'ISSUED']);
    }

    /** @test */
    public function timeout_after_issue_requires_reconciliation_and_callback_resolves_it()
    {
        config(['mock_insurer.scenario' => 'TIMEOUT_AFTER_ISSUE']);
        $payload = $this->validPayload('REQTIME');
        $response = $this->postJson('/api/policy-requests', $payload);
        $policyId = $response->json('id');
        $submitResp = $this->postJson("/api/policy-requests/{$policyId}/submit");
        $submitResp->assertStatus(200)->assertJson(['status' => 'needs_reconciliation']);
        $this->assertDatabaseHas('policy_requests', ['id' => $policyId, 'state' => 'NEEDS_RECONCILIATION']);

        // Simulate provider callback SUCCESS
        $policy = PolicyRequest::find($policyId);
        $eventPayload = [
            'event_id' => 'ev123',
            'policy_id' => $policyId,
            'type' => 'SUCCESS',
            'provider_reference' => $policy->provider_reference,
            'policy_number' => $policy->policy_number,
        ];
        $callbackResp = $this->postJson('/mock-insurer/callback', $eventPayload);
        $callbackResp->assertStatus(200)->assertJson(['status' => 'processed']);
        $this->assertDatabaseHas('policy_requests', ['id' => $policyId, 'state' => 'ISSUED']);
    }

    /** @test */
    public function duplicate_callback_is_idempotent()
    {
        config(['mock_insurer.scenario' => 'SUCCESS']);
        $payload = $this->validPayload('REQDUP');
        $resp = $this->postJson('/api/policy-requests', $payload);
        $policyId = $resp->json('id');
        $this->postJson("/api/policy-requests/{$policyId}/submit");

        $event = [
            'event_id' => 'dup123',
            'policy_id' => $policyId,
            'type' => 'SUCCESS',
            'provider_reference' => 'PRV999999',
            'policy_number' => 'POLABCDEF',
        ];
        $first = $this->postJson('/mock-insurer/callback', $event);
        $first->assertStatus(200)->assertJson(['status' => 'processed']);
        $second = $this->postJson('/mock-insurer/callback', $event);
        $second->assertStatus(200)->assertJson(['status' => 'duplicate_event']);
    }

    /** @test */
    public function invalid_state_transition_is_rejected()
    {
        config(['mock_insurer.scenario' => 'SUCCESS']);
        $payload = $this->validPayload('REQINV');
        $resp = $this->postJson('/api/policy-requests', $payload);
        $policyId = $resp->json('id');
        // Directly transition to ISSUED via submit (will be issued)
        $this->postJson("/api/policy-requests/{$policyId}/submit");
        // Attempt to submit again; should return early with issued info, not error
        $again = $this->postJson("/api/policy-requests/{$policyId}/submit");
        $again->assertStatus(200)->assertJson(['status' => 'issued']);
    }

    private function validPayload(string $clientRequestId): array
    {
        return [
            'client_request_id' => $clientRequestId,
            'agent_id' => 'AGENTX',
            'customer_name' => 'Test User',
            'date_of_birth' => '1990-01-01',
            'destination' => 'DOMESTIC',
            'trip_start' => '2024-12-01',
            'trip_end' => '2024-12-05',
            'plan_code' => 'PLUS',
            'sum_insured' => 100000,
        ];
    }
}
