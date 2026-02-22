<?php

namespace Tests\Feature;

use App\Models\Domains\Vendors\Vendor;
use App\Models\Domains\Vendors\VendorCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Filament\Tenant\Resources\VendorResource;
use App\Filament\Tenant\Resources\VendorCategoryResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VendorsAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => database_path('migrations/system'),
            '--realpath' => true,
        ];
    }

    public function test_tenant_user_with_write_permission_can_create_category_and_vendor(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->bootstrapTenant('bank-31', 'bank-31.localhost');

        Permission::create(['name' => 'VENDORS:READ', 'guard_name' => 'web']);
        Permission::create(['name' => 'VENDORS:WRITE', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('VENDORS:WRITE');

        $this->actingAs($user);

        $this->assertTrue(VendorCategoryResource::canCreate());
        $this->assertTrue(VendorResource::canCreate());

        $category = VendorCategory::create([
            'name' => 'Office Supplies',
            'slug' => null,
            'description' => 'Stationery and supplies',
        ]);

        $vendor = Vendor::create([
            'name' => 'Acme Ltd',
            'category_id' => $category->id,
            'contact_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '123456789',
            'address' => '1 Test St',
            'notes' => 'Preferred vendor',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('vendor_categories', ['id' => $category->id, 'name' => 'Office Supplies']);
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'name' => 'Acme Ltd']);

        // slug should be auto-generated from name when omitted
        $this->assertNotEmpty($category->slug);
        $this->assertStringContainsString('office-supplies', $category->slug);


        tenancy()->end();
    }

    public function test_tenant_user_without_write_permission_cannot_create_or_update(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->bootstrapTenant('bank-32', 'bank-32.localhost');

        Permission::create(['name' => 'VENDORS:READ', 'guard_name' => 'web']);
        Permission::create(['name' => 'VENDORS:WRITE', 'guard_name' => 'web']);

        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertFalse(VendorCategoryResource::canCreate());
        $this->assertFalse(VendorResource::canCreate());

        // create a category as an admin for edit test
        $admin = User::factory()->create();
        $admin->givePermissionTo('VENDORS:WRITE');
        $this->actingAs($admin);

        $category = VendorCategory::create([
            'name' => 'Catering',
            'slug' => 'catering',
            'description' => 'Food vendors',
        ]);

        $vendor = Vendor::create([
            'name' => 'FoodCo',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        tenancy()->end();

        // now re-bootstrap tenant and act as original user to test cannot edit
        $this->bootstrapTenant('bank-32', 'bank-32.localhost');
        $this->actingAs($user);

        $this->assertFalse(VendorResource::canEdit($vendor));
        $this->assertFalse(VendorCategoryResource::canEdit($category));

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

        Artisan::call('migrate:fresh', [
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);

        return $tenant;
    }
}
