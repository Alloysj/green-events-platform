<?php

namespace App\Models\Domains\Evidence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class EvidenceLink extends Model
{
    use TenantConnection;

    protected $fillable = [
        'evidence_artifact_id',
        'linkable_type',
        'linkable_id',
        'report_section_tag',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(EvidenceArtifact::class, 'evidence_artifact_id');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}

