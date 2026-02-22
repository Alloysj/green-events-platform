<?php

namespace Tests\Feature;

use App\Models\Domains\Vendors\Vendor;
use App\Models\Domains\Vendors\VendorComplianceProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VendorComplianceProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => database_path('migrations/system'),
            '--realpath' => true,
        ];
    }

    public function test_create_and_update_compliance_profile_with_permission(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->bootstrapTenant('bank-51', 'bank-51.localhost');

        Permission::create(['name' => 'VENDORS:WRITE', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('VENDORS:WRITE');

        $vendor = Vendor::create(['name' => 'SustainVendor', 'is_active' => true]);

        $this->actingAs($user);

        $profile = $vendor->updateComplianceProfileBy($user, [
            'packaging_policy' => 'recyclable',
            'reusable_options' => true,
            'reusable_options_notes' => 'Returnable crates',
            'waste_handling_capability' => 'offsite',
            'distance_km' => 12.5,
            'certifications' => ['ISO14001', 'EMAS'],
            'additional_metadata' => ['notes' => 'Verified'],
        ]);

        $this->assertInstanceOf(VendorComplianceProfile::class, $profile);

        $this->assertDatabaseHas('vendor_compliance_profiles', ['vendor_id' => $vendor->id, 'packaging_policy' => 'recyclable']);

        // update
        $vendor->updateComplianceProfileBy($user, ['packaging_policy' => 'minimal']);

        $this->assertDatabaseHas('vendor_compliance_profiles', ['vendor_id' => $vendor->id, 'packaging_policy' => 'minimal']);

        tenancy()->end();
    }

    public function test_update_compliance_profile_requires_permission(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->bootstrapTenant('bank-52', 'bank-52.localhost');

        Permission::create(['name' => 'VENDORS:WRITE', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('TenantAdmin');

        $vendor = Vendor::create(['name' => 'SecureVendor', 'is_active' => true]);

        // unauthorized user
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->actingAs($user);
        $vendor->updateComplianceProfileBy($user, ['packaging_policy' => 'minimal']);

        tenancy()->end();

        // admin should work
        $this->bootstrapTenant('bank-52', 'bank-52.localhost');
        $this->actingAs($admin);
        $profile = $vendor->updateComplianceProfileBy($admin, ['packaging_policy' => 'minimal']);
        $this->assertInstanceOf(VendorComplianceProfile::class, $profile);

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
