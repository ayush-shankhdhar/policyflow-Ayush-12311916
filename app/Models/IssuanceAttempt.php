<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssuanceAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_request_id',
        'provider_reference',
        'status',
        'response_payload',
    ];

    protected $casts = [
        'response_payload' => 'json',
    ];

    public function policyRequest()
    {
        return $this->belongsTo(PolicyRequest::class);
    }
}
?>
