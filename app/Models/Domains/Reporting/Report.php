<?php

namespace App\Models\Domains\Reporting;

use App\Models\Domains\Events\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class Report extends Model
{
    use TenantConnection;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'event_id',
        'title',
        'status',
        'generated_at',
        'published_at',
        'metadata',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_GENERATED => 'Generated',
            self::STATUS_PUBLISHED => 'Published',
        ];
    }
}

