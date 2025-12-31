<?php

use App\Http\Controllers\Tenant\EvidenceDownloadController;
use App\Http\Controllers\Tenant\ReportExportDownloadController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

$tenancyMiddleware = app()->environment('local')
    ? [InitializeTenancyByDomain::class]
    : [InitializeTenancyByDomainOrSubdomain::class, PreventAccessFromCentralDomains::class];

Route::middleware($tenancyMiddleware)->group(function () {
    Route::get('/tenant/ping', function () {
        return response()->json(['tenant' => tenant('id')]);
    });

    Route::get('/app/evidence/{evidence}/download', EvidenceDownloadController::class)
        ->middleware(['auth', 'signed'])
        ->name('tenant.evidence.download');

    Route::get('/app/reports/exports/{reportExport}/download', ReportExportDownloadController::class)
        ->middleware(['auth', 'signed'])
        ->name('tenant.report-exports.download');
});
