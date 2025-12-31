<?php

namespace App\Filament\Tenant\Resources\EvidenceArtifactResource\Pages;

use App\Filament\Tenant\Resources\EvidenceArtifactResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditEvidenceArtifact extends EditRecord
{
    protected static string $resource = EvidenceArtifactResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $disk = config('evidence.disk', 'local');
        $path = $data['storage_path'] ?? null;

        $data['storage_disk'] = $disk;

        if ($path) {
            $data['mime_type'] = Storage::disk($disk)->mimeType($path);
            $data['file_size'] = Storage::disk($disk)->size($path);
        }

        return $data;
    }
}
