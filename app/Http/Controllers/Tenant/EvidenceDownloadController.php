<?php

namespace App\Http\Controllers\Tenant;

use App\Models\Domains\Evidence\EvidenceArtifact;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class EvidenceDownloadController extends Controller
{
    public function __invoke(Request $request, EvidenceArtifact $evidence)
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(Roles::tenantRoles())) {
            abort(403);
        }

        $disk = $evidence->storage_disk ?: config('evidence.disk', 'local');
        $path = $evidence->storage_path;

        if (! $path || ! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $fileName = $evidence->file_name ?: basename($path);
        $mimeType = $evidence->mime_type ?: Storage::disk($disk)->mimeType($path);

        return Storage::disk($disk)->download($path, $fileName, [
            'Content-Type' => $mimeType,
        ]);
    }
}
