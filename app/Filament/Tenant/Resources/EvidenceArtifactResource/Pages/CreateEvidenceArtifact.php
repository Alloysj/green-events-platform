<?php

namespace App\Filament\Tenant\Resources\EvidenceArtifactResource\Pages;

use App\Filament\Tenant\Resources\EvidenceArtifactResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateEvidenceArtifact extends CreateRecord
{
    protected static string $resource = EvidenceArtifactResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $disk = config('evidence.disk', 'local');
        $path = $data['storage_path'] ?? null;

        $data['storage_disk'] = $disk;
        $data['uploaded_by'] = auth()->id();

        if ($path) {
            $data['mime_type'] = Storage::disk($disk)->mimeType($path);
            $data['file_size'] = Storage::disk($disk)->size($path);
        }

        return $data;
    }
}
