<?php

namespace Tests\Feature;

use App\Models\Domains\Vendors\VendorCategory;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class VendorCategorySlugTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => database_path('migrations/system'),
            '--realpath' => true,
        ];
    }

    public function test_slug_is_auto_generated_and_unique(): void
    {
        $this->bootstrapTenant('bank-41', 'bank-41.localhost');

        $c1 = VendorCategory::create([
            'name' => 'Office Supplies',
            'slug' => null,
        ]);

        $this->assertNotEmpty($c1->slug);
        $this->assertEquals('office-supplies', $c1->slug);

        $c2 = VendorCategory::create([
            'name' => 'Office Supplies',
            'slug' => null,
        ]);

        $this->assertNotEmpty($c2->slug);
        $this->assertNotEquals($c1->slug, $c2->slug);
        $this->assertStringStartsWith('office-supplies', $c2->slug);

        tenancy()->end();
    }

    private function bootstrapTenant(string $tenantId, string $domain)
    {
        $tenant = Tenant::create([
            'id' => $tenantId,
            'database' => 'tenant_' . $tenantId,
        ]);

        $tenant->domains()->create(['domain' => $domain]);
        $tenant->createDatabase();

        tenancy()->initialize($tenant);

        Artisan::call('migrate:fresh', [
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);

        return $tenant;
    }
}
