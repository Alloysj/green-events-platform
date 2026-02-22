<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Domains\Vendors\Vendor;
use App\Models\Domains\Vendors\VendorApproval;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VendorApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => database_path('migrations/system'),
            '--realpath' => true,
        ];
    }

    public function test_approving_vendor_creates_approval_and_audit_log(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->bootstrapTenant('bank-33', 'bank-33.localhost');

        Permission::create(['name' => 'VENDORS:APPROVE', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('VENDORS:APPROVE');

        $vendor = Vendor::create(['name' => 'ApproveMe', 'is_active' => true]);

        $this->actingAs($user);

        $approval = $vendor->approveBy($user, now()->addDays(180), 'Annual approval');

        $this->assertInstanceOf(VendorApproval::class, $approval);
        $this->assertDatabaseHas('vendor_approvals', ['id' => $approval->id, 'vendor_id' => $vendor->id, 'status' => VendorApproval::STATUS_APPROVED]);

        $this->assertDatabaseHas('audit_logs', ['event' => 'vendor.approved']);

        tenancy()->end();
    }

    public function test_expired_vendor_is_not_treated_as_approved(): void
    {
        $this->bootstrapTenant('bank-34', 'bank-34.localhost');

        $vendor = Vendor::create(['name' => 'OldVendor', 'is_active' => true]);

        $vendor->approvals()->create([
            'status' => VendorApproval::STATUS_APPROVED,
            'approved_by' => null,
            'approved_at' => now()->subYear(),
            'expires_at' => now()->subDays(1),
        ]);

        $this->assertFalse($vendor->isApproved());
        $this->assertTrue($vendor->isExpired());

        tenancy()->end();
    }

    public function test_permission_enforcement_for_approve_and_revoke(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->bootstrapTenant('bank-35', 'bank-35.localhost');

        Permission::create(['name' => 'VENDORS:APPROVE', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole(Roles::TENANT_ADMIN);

        $vendor = Vendor::create(['name' => 'SecureVendor', 'is_active' => true]);

        // user without permission should not be able to approve/revoke
        $this->actingAs($user);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $vendor->approveBy($user);

        // admin role should be able to approve/revoke
        $this->actingAs($admin);
        $approval = $vendor->approveBy($admin);
        $this->assertInstanceOf(VendorApproval::class, $approval);

        $this->actingAs($admin);
        $revocation = $vendor->revokeBy($admin, 'No longer eligible');
        $this->assertInstanceOf(VendorApproval::class, $revocation);

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
