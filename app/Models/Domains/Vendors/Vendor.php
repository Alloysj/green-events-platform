<?php

namespace App\Models\Domains\Vendors;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class Vendor extends Model
{
    use TenantConnection;

    protected $fillable = [
        'name',
        'category_id',
        'contact_name',
        'email',
        'phone',
        'address',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(VendorCategory::class, 'category_id');
    }

    public function approvals()
    {
        return $this->hasMany(VendorApproval::class, 'vendor_id');
    }

    public function complianceProfile()
    {
        return $this->hasOne(VendorComplianceProfile::class, 'vendor_id');
    }

    public function latestApproval(): ?VendorApproval
    {
        return $this->approvals()->orderByDesc('id')->first();
    }

    public function isApproved(): bool
    {
        $approval = $this->latestApproval();

        if (! $approval) {
            return false;
        }

        if ($approval->status !== VendorApproval::STATUS_APPROVED) {
            return false;
        }

        if ($approval->expires_at && $approval->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        $approval = $this->latestApproval();

        if (! $approval) {
            return false;
        }

        return $approval->status === VendorApproval::STATUS_APPROVED && $approval->expires_at && $approval->expires_at->isPast();
    }

    public function needsReverification(): bool
    {
        $approval = $this->latestApproval();

        if (! $approval) {
            return false;
        }

        if ($approval->status !== VendorApproval::STATUS_APPROVED || ! $approval->expires_at) {
            return false;
        }

        $days = config('vendors.reverification_days', 30);

        return $approval->expires_at->isFuture() && $approval->expires_at->lessThanOrEqualTo(now()->addDays($days));
    }

    /**
     * Approve this vendor; will create a VendorApproval record and write an audit log.
     * Throws AuthorizationException if the actor cannot approve.
     */
    public function approveBy(\App\Models\User $actor, ?\Carbon\Carbon $expiresAt = null, ?string $notes = null)
    {
        if (! $actor->can('VENDORS:APPROVE') && ! $actor->hasRole(\App\Support\Roles::TENANT_ADMIN)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('User cannot approve vendors');
        }

        $approval = $this->approvals()->create([
            'status' => VendorApproval::STATUS_APPROVED,
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'expires_at' => $expiresAt,
            'notes' => $notes,
        ]);

        app(AuditLogger::class)->log('vendor.approved', [
            'vendor_id' => $this->id,
            'expires_at' => $approval->expires_at?->toDateTimeString(),
            'notes' => $notes,
        ], $actor);

        return $approval;
    }

    public function revokeBy(\App\Models\User $actor, ?string $notes = null)
    {
        if (! $actor->can('VENDORS:APPROVE') && ! $actor->hasRole(\App\Support\Roles::TENANT_ADMIN)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('User cannot revoke vendor approvals');
        }

        $approval = $this->approvals()->create([
            'status' => VendorApproval::STATUS_REVOKED,
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'expires_at' => null,
            'notes' => $notes,
        ]);

        app(AuditLogger::class)->log('vendor.revoked', [
            'vendor_id' => $this->id,
            'notes' => $notes,
        ], $actor);

        return $approval;
    }

    /**
     * Update or create the vendor compliance profile, enforcing write permissions.
     */
    public function updateComplianceProfileBy(\App\Models\User $actor, array $data)
    {
        if (! $actor->can('VENDORS:WRITE') && ! $actor->hasRole(\App\Support\Roles::TENANT_ADMIN)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('User cannot update vendor compliance profiles');
        }

        $profile = $this->complianceProfile();

        if ($profile->exists()) {
            $profile->update($data);
            return $profile;
        }

        return $this->complianceProfile()->create($data);
    }
}


