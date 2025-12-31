<?php

namespace App\Models\Domains\Reporting;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class ReportExport extends Model
{
    use TenantConnection;

    public const TYPE_PDF = 'pdf';
    public const TYPE_JSON = 'json';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'report_id',
        'type',
        'status',
        'storage_disk',
        'storage_path',
        'mime_type',
        'error_message',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signedDownloadUrl(): string
    {
        $minutes = config('reporting.signed_url_ttl', 10);

        return URL::temporarySignedRoute(
            'tenant.report-exports.download',
            now()->addMinutes($minutes),
            ['reportExport' => $this->getKey()]
        );
    }
}

