<?php

namespace App\Models\Domains\Evidence;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class EvidenceArtifact extends Model
{
    use TenantConnection;

    protected $fillable = [
        'file_name',
        'storage_disk',
        'storage_path',
        'mime_type',
        'file_size',
        'uploaded_by',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function links(): HasMany
    {
        return $this->hasMany(EvidenceLink::class, 'evidence_artifact_id');
    }

    public function signedDownloadUrl(): string
    {
        $minutes = config('evidence.signed_url_ttl', 10);

        return URL::temporarySignedRoute(
            'tenant.evidence.download',
            now()->addMinutes($minutes),
            ['evidence' => $this->getKey()]
        );
    }
}

