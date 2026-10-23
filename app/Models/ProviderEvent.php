<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_request_id',
        'event_id',
        'type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'json',
    ];

    public function policyRequest()
    {
        return $this->belongsTo(PolicyRequest::class);
    }
}
?>
