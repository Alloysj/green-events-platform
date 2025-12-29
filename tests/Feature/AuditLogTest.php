<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => database_path('migrations/system'),
            '--realpath' => true,
        ];
    }

    public function test_auth_and_role_events_create_audit_logs(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant = Tenant::create([
            'id' => 'bank-3',
            'database' => 'tenant_bank_3',
        ]);

        $tenant->createDatabase();

        tenancy()->initialize($tenant);

        Artisan::call('migrate', [
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);

        $user = User::factory()->create();

        event(new Login('web', $user, false));

        Role::create(['name' => Roles::TENANT_ADMIN]);
        $user->assignRole(Roles::TENANT_ADMIN);

        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.login']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'role.assigned']);

        tenancy()->end();
    }
}
