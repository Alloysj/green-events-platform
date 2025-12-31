<?php

namespace App\Http\Controllers\Tenant;

use App\Models\Domains\Reporting\ReportExport;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class ReportExportDownloadController extends Controller
{
    public function __invoke(Request $request, ReportExport $reportExport)
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(Roles::tenantRoles())) {
            abort(403);
        }

        $disk = $reportExport->storage_disk ?: config('reporting.disk', 'local');
        $path = $reportExport->storage_path;

        if (! $path || ! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $fileName = basename($path);
        $mimeType = $reportExport->mime_type ?: Storage::disk($disk)->mimeType($path);

        return Storage::disk($disk)->download($path, $fileName, [
            'Content-Type' => $mimeType,
        ]);
    }
}
