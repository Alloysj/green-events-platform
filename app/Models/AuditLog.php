<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\UsesTenantConnection;

class AuditLog extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'user_id',
        'event',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
