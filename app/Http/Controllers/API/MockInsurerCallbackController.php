<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProviderEvent;
use App\Models\PolicyRequest;
use App\Services\PolicyStateMachine;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MockInsurerCallbackController extends Controller
{
    /**
     * Handle provider callbacks.
     * Expected payload: {"event_id":"...","policy_id":<id>,"type":"SUCCESS","provider_reference":"...","policy_number":"..."}
     */
    public function handle(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|string',
            'policy_id' => 'required|integer|exists:policy_requests,id',
            'type' => 'required|in:SUCCESS,TEMPORARY_FAILURE,TIMEOUT_AFTER_ISSUE,DELAYED_CALLBACK,DUPLICATE_CALLBACK',
            'provider_reference' => 'nullable|string',
            'policy_number' => 'nullable|string',
        ]);

        // Idempotent: ensure event_id not processed before
        $existing = ProviderEvent::where('event_id', $validated['event_id'])->first();
        if ($existing) {
            return response()->json(['status' => 'duplicate_event'], Response::HTTP_OK);
        }

        $policy = PolicyRequest::findOrFail($validated['policy_id']);
        ProviderEvent::create($validated);

        $stateMachine = app(PolicyStateMachine::class);

        // Apply effects based on type
        if ($validated['type'] === 'SUCCESS') {
            $policy->provider_reference = $validated['provider_reference'];
            $policy->policy_number = $validated['policy_number'];
            $stateMachine->transition($policy, 'ISSUED');
        } elseif ($validated['type'] === 'TEMPORARY_FAILURE') {
            $stateMachine->transition($policy, 'FAILED');
        } elseif ($validated['type'] === 'TIMEOUT_AFTER_ISSUE') {
            $policy->provider_reference = $validated['provider_reference'];
            $policy->policy_number = $validated['policy_number'];
            $stateMachine->transition($policy, 'NEEDS_RECONCILIATION');
        }
        // other types could be handled similarly

        return response()->json(['status' => 'processed'], Response::HTTP_OK);
    }
}
?>
