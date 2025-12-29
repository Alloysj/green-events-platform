<?php

namespace Tests\Feature;

use App\Services\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => database_path('migrations/system'),
            '--realpath' => true,
        ];
    }

    public function test_tenant_provisioning_creates_database_and_admin_user(): void
    {
        config(['tenancy.central_domains' => ['localhost']]);

        $tenant = app(TenantProvisioner::class)->provision(
            'bank-1',
            'bank-1.localhost',
            'Admin User',
            'admin@bank-1.test',
            'secret',
        );

        $this->assertDatabaseHas('tenants', ['id' => 'bank-1']);
        $this->assertDatabaseHas('domains', ['domain' => 'bank-1.localhost']);

        tenancy()->initialize($tenant);
        $this->assertDatabaseHas('users', ['email' => 'admin@bank-1.test']);
        tenancy()->end();
    }
}
