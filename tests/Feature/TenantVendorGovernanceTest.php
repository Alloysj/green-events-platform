<?php

namespace Tests\Feature;

use App\Filament\Tenant\Resources\VendorCategoryResource;
use App\Filament\Tenant\Resources\VendorResource;
use App\Models\Domains\Vendors\Vendor;
use App\Models\Domains\Vendors\VendorCategory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantVendorGovernanceTest extends TestCase
{
    use DatabaseMigrations;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => database_path('migrations/system'),
            '--realpath' => true,
        ];
    }

    public function test_tenant_user_can_create_vendor_and_category_with_write_permission(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->bootstrapTenant('bank-vendor-1');

        $user = User::factory()->create();

        Permission::firstOrCreate(['name' => 'VENDORS:READ', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'VENDORS:WRITE', 'guard_name' => 'web']);
        $user->givePermissionTo(['VENDORS:READ', 'VENDORS:WRITE']);

        $this->actingAs($user);

        $this->assertTrue(VendorCategoryResource::canCreate());
        $this->assertTrue(VendorResource::canCreate());

        $category = VendorCategory::query()->create([
            'name' => 'Catering',
            'slug' => 'catering',
            'description' => 'Food and beverage vendors',
        ]);

        $vendor = Vendor::query()->create([
            'name' => 'Fresh Foods Ltd',
            'category_id' => $category->id,
            'contact_name' => 'Jane Doe',
            'email' => 'jane@freshfoods.test',
            'phone' => '+254700000001',
            'address' => 'Nairobi',
            'notes' => 'Preferred',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('vendor_categories', [
            'id' => $category->id,
            'name' => 'Catering',
        ], 'tenant');

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'name' => 'Fresh Foods Ltd',
            'category_id' => $category->id,
        ], 'tenant');

        tenancy()->end();
    }

    public function test_tenant_user_without_write_permission_cannot_create_or_update(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->bootstrapTenant('bank-vendor-2');

        $user = User::factory()->create();

        Permission::firstOrCreate(['name' => 'VENDORS:READ', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'VENDORS:WRITE', 'guard_name' => 'web']);
        $user->givePermissionTo('VENDORS:READ');

        $this->actingAs($user);

        $category = VendorCategory::query()->create([
            'name' => 'Transport',
            'slug' => 'transport',
        ]);

        $vendor = Vendor::query()->create([
            'name' => 'City Rides',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $this->assertFalse(VendorCategoryResource::canCreate());
        $this->assertFalse(VendorResource::canCreate());
        $this->assertFalse(VendorCategoryResource::canEdit($category));
        $this->assertFalse(VendorResource::canEdit($vendor));

        tenancy()->end();
    }

    private function bootstrapTenant(string $tenantId): Tenant
    {
        $tenant = Tenant::create([
            'id' => $tenantId,
            'database' => 'tenant_' . $tenantId,
        ]);

        $tenantDriver = config('database.connections.tenant.driver');

        if ($tenantDriver === 'sqlite') {
            $tenantDbPath = database_path('tenant_' . $tenantId . '.sqlite');
            if (file_exists($tenantDbPath)) {
                unlink($tenantDbPath);
            }
            touch($tenantDbPath);

            config([
                'database.connections.tenant.database' => $tenantDbPath,
                'database.connections.tenant.foreign_key_constraints' => true,
            ]);
        } else {
            $tenant->createDatabase();
        }

        tenancy()->initialize($tenant);

        Artisan::call('migrate:fresh', [
            '--path' => database_path('migrations/tenant'),
            '--database' => 'tenant',
            '--realpath' => true,
            '--force' => true,
        ]);

        return $tenant;
    }
}
