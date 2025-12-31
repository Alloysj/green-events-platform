<?php

namespace Tests\Feature;

use App\Models\Domains\Evidence\EvidenceArtifact;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EvidenceAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => database_path('migrations/system'),
            '--realpath' => true,
        ];
    }

    public function test_authorized_user_can_download_evidence(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->bootstrapTenant('bank-20', 'bank-20.localhost');

        Role::create(['name' => Roles::TENANT_ADMIN, 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole(Roles::TENANT_ADMIN);

        Storage::fake('local');
        Storage::disk('local')->put('evidence/demo.txt', 'hello');

        $evidence = EvidenceArtifact::create([
            'file_name' => 'demo.txt',
            'storage_disk' => 'local',
            'storage_path' => 'evidence/demo.txt',
            'mime_type' => 'text/plain',
            'file_size' => 5,
            'uploaded_by' => $user->id,
        ]);

        URL::forceRootUrl('http://bank-20.localhost');

        $url = URL::temporarySignedRoute(
            'tenant.evidence.download',
            now()->addMinutes(5),
            ['evidence' => $evidence->id]
        );

        $response = $this
            ->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'bank-20.localhost'])
            ->get($url);

        $response->assertOk();

        tenancy()->end();
    }

    public function test_user_without_role_cannot_download_evidence(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->bootstrapTenant('bank-21', 'bank-21.localhost');

        $user = User::factory()->create();

        Storage::fake('local');
        Storage::disk('local')->put('evidence/demo.txt', 'hello');

        $evidence = EvidenceArtifact::create([
            'file_name' => 'demo.txt',
            'storage_disk' => 'local',
            'storage_path' => 'evidence/demo.txt',
            'mime_type' => 'text/plain',
            'file_size' => 5,
            'uploaded_by' => $user->id,
        ]);

        URL::forceRootUrl('http://bank-21.localhost');

        $url = URL::temporarySignedRoute(
            'tenant.evidence.download',
            now()->addMinutes(5),
            ['evidence' => $evidence->id]
        );

        $response = $this
            ->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'bank-21.localhost'])
            ->get($url);

        $response->assertForbidden();

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
