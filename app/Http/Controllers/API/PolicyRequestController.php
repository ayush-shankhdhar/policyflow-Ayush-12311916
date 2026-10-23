<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePolicyRequest;
use App\Models\PolicyRequest;
use App\Services\PremiumCalculator;
use App\Services\PolicyStateMachine;
use App\Services\MockInsurerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PolicyRequestController extends Controller
{
    public function store(StorePolicyRequest $request)
    {
        $data = $request->validated();
        // Calculate premium
        $premiumCalculator = app(PremiumCalculator::class);
        $premium = $premiumCalculator->calculate($data);

        try {
            return DB::transaction(function () use ($data, $premium) {
                $policy = PolicyRequest::create(array_merge($data, [
                    'premium' => $premium,
                    'state' => 'RECEIVED',
                ]));
                // Transition to VALIDATED
                $stateMachine = app(PolicyStateMachine::class);
                $stateMachine->transition($policy, 'VALIDATED');
                return response()->json([
                    'id' => $policy->id,
                    'status' => 'created',
                    'premium' => $policy->premium,
                    'state' => $policy->state,
                ], Response::HTTP_CREATED);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Assume unique constraint violation on (agent_id, client_request_id)
            $existing = PolicyRequest::where('agent_id', $data['agent_id'])
                ->where('client_request_id', $data['client_request_id'])
                ->first();
            if ($existing) {
                return response()->json([
                    'id' => $existing->id,
                    'status' => 'duplicate',
                    'premium' => $existing->premium,
                    'state' => $existing->state,
                ], Response::HTTP_OK);
            }
            throw $e; // rethrow if other DB error
        }
    }

    public function show($id)
    {
        $policy = PolicyRequest::with(['attempts', 'providerEvents'])->findOrFail($id);
        return response()->json([
            'id' => $policy->id,
            'client_request_id' => $policy->client_request_id,
            'agent_id' => $policy->agent_id,
            'customer_name' => $policy->customer_name,
            'date_of_birth' => $policy->date_of_birth,
            'destination' => $policy->destination,
            'trip_start' => $policy->trip_start,
            'trip_end' => $policy->trip_end,
            'plan_code' => $policy->plan_code,
            'sum_insured' => $policy->sum_insured,
            'premium' => $policy->premium,
            'state' => $policy->state,
            'provider_reference' => $policy->provider_reference,
            'policy_number' => $policy->policy_number,
            'attempts' => $policy->attempts->map(function ($a) {
                return $a->only(['id', 'status', 'provider_reference', 'created_at']);
            }),
            'events' => $policy->providerEvents->map(fn($e)=>$e->only(['id','type','event_id','payload','created_at'])),
        ]);
    }

    public function submit($id)
    {
        $policy = PolicyRequest::findOrFail($id);
        // Idempotent: if already ISSUED, return existing info
        if ($policy->state === 'ISSUED') {
            return response()->json([
                'id' => $policy->id,
                'status' => 'issued',
                'policy_number' => $policy->policy_number,
                'provider_reference' => $policy->provider_reference,
                'state' => $policy->state,
            ]);
        }
        $stateMachine = app(PolicyStateMachine::class);
        // Ensure we are at a state where submission is allowed
        if (!in_array($policy->state, ['VALIDATED', 'FAILED', 'NEEDS_RECONCILIATION'])) {
            return response()->json(['error' => 'Policy not in a submittable state'], Response::HTTP_BAD_REQUEST);
        }
        // Transition to SUBMITTED
        $stateMachine->transition($policy, 'SUBMITTED');

        $mockInsurer = app(MockInsurerService::class);
        $result = $mockInsurer->submit($policy);

        switch ($result['status']) {
            case 'SUCCESS':
                $policy->provider_reference = $result['provider_reference'];
                $policy->policy_number = $result['policy_number'];
                $policy->save();
                $stateMachine->transition($policy, 'PENDING_CONFIRMATION');
                // Simulate immediate confirmation for SUCCESS scenario
                $stateMachine->transition($policy, 'ISSUED');
                return response()->json(['status' => 'issued', 'policy_number' => $policy->policy_number]);
            case 'PENDING':
                $stateMachine->transition($policy, 'PENDING_CONFIRMATION');
                return response()->json(['status' => 'pending_confirmation']);
            case 'FAILURE':
                $stateMachine->transition($policy, 'FAILED');
                return response()->json(['status' => 'failed']);
            case 'TIMEOUT':
                $policy->provider_reference = $result['provider_reference'];
                $policy->policy_number = $result['policy_number'];
                $policy->save();
                $stateMachine->transition($policy, 'NEEDS_RECONCILIATION');
                return response()->json(['status' => 'needs_reconciliation']);
            default:
                $stateMachine->transition($policy, 'FAILED');
                return response()->json(['status' => 'failed']);
        }
    }

    public function index(Request $request)
    {
        $query = PolicyRequest::query();
        if ($request->has('state')) {
            $query->where('state', $request->query('state'));
        }
        $perPage = $request->query('per_page', 15);
        $policies = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($policies);
    }
}
?>
