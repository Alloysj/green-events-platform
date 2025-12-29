<?php

namespace Tests\Feature;

use App\Filament\System\Resources\TenantResource;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SystemRoleRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => database_path('migrations/system'),
            '--realpath' => true,
        ];
    }

    public function test_only_system_roles_can_view_tenants_resource(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertFalse(TenantResource::canViewAny());

        Role::create(['name' => Roles::SYSTEM_ADMIN]);
        $user->assignRole(Roles::SYSTEM_ADMIN);

        $this->assertTrue(TenantResource::canViewAny());
    }
}
