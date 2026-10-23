<?php

namespace Tests\Feature;

use App\Models\PolicyRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_valid_policy_request_and_returns_201()
    {
        $payload = [
            'client_request_id' => 'REQ123',
            'agent_id' => 'AGENT1',
            'customer_name' => 'John Doe',
            'date_of_birth' => '1990-01-01',
            'destination' => 'DOMESTIC',
            'trip_start' => '2024-12-01',
            'trip_end' => '2024-12-05',
            'plan_code' => 'PLUS',
            'sum_insured' => 100000,
        ];

        $response = $this->postJson('/api/policy-requests', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('premium', '100.00');
    }

    /** @test */
    public function it_prevents_duplicate_policy_requests_and_returns_200()
    {
        $payload = [
            'client_request_id' => 'REQDUP',
            'agent_id' => 'AGENT2',
            'customer_name' => 'Jane Doe',
            'date_of_birth' => '1985-06-15',
            'destination' => 'INTERNATIONAL',
            'trip_start' => '2024-12-10',
            'trip_end' => '2024-12-12',
            'plan_code' => 'BASIC',
            'sum_insured' => 300000,
        ];
        $this->postJson('/api/policy-requests', $payload)->assertStatus(201);
        $this->postJson('/api/policy-requests', $payload)->assertStatus(200)->assertJsonPath('status', 'duplicate');
    }

    /** @test */
    public function it_validates_trip_dates_and_returns_422()
    {
        $payload = [
            'client_request_id' => 'REQBAD',
            'agent_id' => 'AGENT3',
            'customer_name' => 'Bad Trip',
            'date_of_birth' => '1995-05-05',
            'destination' => 'DOMESTIC',
            'trip_start' => '2024-12-10',
            'trip_end' => '2024-12-05', // end before start
            'plan_code' => 'BASIC',
            'sum_insured' => 100000,
        ];
        $this->postJson('/api/policy-requests', $payload)->assertStatus(422);
    }

    /** @test */
    public function premium_calculation_matches_example()
    {
        $payload = [
            'client_request_id' => 'REQEX',
            'agent_id' => 'AGENT4',
            'customer_name' => 'Example',
            'date_of_birth' => '1990-01-01',
            'destination' => 'INTERNATIONAL',
            'trip_start' => '2024-12-01',
            'trip_end' => '2024-12-05', // 5 days
            'plan_code' => 'PLUS',
            'sum_insured' => 300000,
        ];
        $response = $this->postJson('/api/policy-requests', $payload);
        $response->assertStatus(201)->assertJsonPath('premium', '225.00');
    }
}
?>
