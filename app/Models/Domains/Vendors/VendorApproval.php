<?php

namespace App\Models\Domains\Vendors;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class VendorApproval extends Model
{
    use TenantConnection;

    public const STATUS_APPROVED = 'approved';
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'vendor_id',
        'status',
        'approved_by',
        'approved_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
