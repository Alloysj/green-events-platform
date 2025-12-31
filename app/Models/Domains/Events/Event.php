<?php

namespace App\Models\Domains\Events;

use App\Models\Domains\Events\EventApproval;
use App\Models\Domains\Events\EventStatusHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class Event extends Model
{
    use TenantConnection;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REPORT_PUBLISHED = 'report_published';

    public const APPROVAL_ESG = 'esg';
    public const APPROVAL_PROCUREMENT = 'procurement';
    public const APPROVAL_RISK = 'risk';

    protected $fillable = [
        'name',
        'status',
        'requires_risk_approval',
    ];

    protected $casts = [
        'requires_risk_approval' => 'bool',
    ];

    public function approvals(): HasMany
    {
        return $this->hasMany(EventApproval::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(EventStatusHistory::class);
    }

    public function approvalTypes(): array
    {
        $types = [self::APPROVAL_ESG, self::APPROVAL_PROCUREMENT];

        if ($this->requires_risk_approval) {
            $types[] = self::APPROVAL_RISK;
        }

        return $types;
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_APPROVAL => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REPORT_PUBLISHED => 'Report Published',
        ];
    }
}

