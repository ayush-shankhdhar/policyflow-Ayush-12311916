<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class PolicyRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'client_request_id',
        'customer_name',
        'date_of_birth',
        'destination',
        'trip_start',
        'trip_end',
        'plan_code',
        'sum_insured',
        'premium',
        'state',
        'provider_reference',
        'policy_number',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'trip_start' => 'date',
        'trip_end' => 'date',
        'premium' => 'decimal:2',
    ];

    /**
     * Relationship to issuance attempts.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(IssuanceAttempt::class);
    }

    /**
     * Relationship to provider events.
     */
    public function providerEvents(): HasMany
    {
        return $this->hasMany(ProviderEvent::class);
    }
}
?>
