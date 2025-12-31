<?php

namespace Tests\Feature;

use App\Jobs\GenerateReportJson;
use App\Jobs\GenerateReportPdf;
use App\Models\Domains\Events\Event;
use App\Models\Domains\Reporting\Report;
use App\Models\Domains\Reporting\ReportExport;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => database_path('migrations/system'),
            '--realpath' => true,
        ];
    }

    public function test_json_export_creates_file(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->bootstrapTenant('bank-30', 'bank-30.localhost');

        Role::create(['name' => Roles::TENANT_ADMIN, 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole(Roles::TENANT_ADMIN);

        $event = Event::create(['name' => 'Report Event', 'status' => Event::STATUS_DRAFT]);

        $report = Report::create([
            'title' => 'Quarterly Report',
            'status' => Report::STATUS_DRAFT,
            'event_id' => $event->id,
        ]);

        Storage::fake('local');
        config(['reporting.disk' => 'local']);

        GenerateReportJson::dispatchSync($report->id, $user->id);

        $export = ReportExport::query()->where('type', ReportExport::TYPE_JSON)->first();

        $this->assertNotNull($export);
        $this->assertSame(ReportExport::STATUS_COMPLETE, $export->status);
        Storage::disk('local')->assertExists($export->storage_path);

        tenancy()->end();
    }

    public function test_pdf_export_fails_without_binary(): void
    {
        $this->bootstrapTenant('bank-31', 'bank-31.localhost');

        $report = Report::create([
            'title' => 'PDF Report',
            'status' => Report::STATUS_DRAFT,
        ]);

        config(['reporting.pdf_binary' => null]);

        GenerateReportPdf::dispatchSync($report->id, null);

        $export = ReportExport::query()->where('type', ReportExport::TYPE_PDF)->first();

        $this->assertNotNull($export);
        $this->assertSame(ReportExport::STATUS_FAILED, $export->status);

        tenancy()->end();
    }

    private function bootstrapTenant(string $tenantId, string $domain): Tenant
    {
        $tenant = Tenant::create([
            'id' => $tenantId,
            'database' => 'tenant_' . $tenantId,
        ]);

        $tenant->domains()->create(['domain' => $domain]);
        $tenant->createDatabase();

        tenancy()->initialize($tenant);

        Artisan::call('migrate', [
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);

        return $tenant;
    }
}
