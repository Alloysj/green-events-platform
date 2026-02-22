<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TenantContextSwitchingTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => database_path('migrations/system'),
            '--realpath' => true,
        ];
    }

    public function test_tenant_routes_initialize_tenant_by_domain(): void
    {
        config(['tenancy.central_domains' => ['localhost']]);

        $tenant = Tenant::create([
            'id' => 'bank-2',
            'database' => 'tenant_bank_2',
        ]);

        $tenant->domains()->create(['domain' => 'bank-2.localhost']);
        $tenant->createDatabase();

        tenancy()->initialize($tenant);
        Artisan::call('migrate:fresh', [
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);
        tenancy()->end();

        $this->get('/tenant/ping', ['HTTP_HOST' => 'bank-2.localhost'])
            ->assertOk()
            ->assertJson(['tenant' => 'bank-2']);
    }
}
