<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Stancl\Tenancy\Database\DatabaseManager;

class TenantProvisioner
{
    public function provision(
        string $tenantId,
        string $domain,
        string $adminName,
        string $adminEmail,
        string $adminPassword,
        ?string $databaseName = null
    ): Tenant {
        $databaseName = $databaseName ?: 'tenant_'.Str::slug($tenantId, '_');

        $tenant = Tenant::create([
            'id' => $tenantId,
        ]);

        $tenant->domains()->create(['domain' => $domain]);
        $tenant->setInternal('db_name', $databaseName)->save();
        $tenant->database()->makeCredentials();

        $manager = $tenant->database()->manager();
        $dbName = $tenant->database()->getName();

        if ($manager->databaseExists($dbName)) {
            throw new \RuntimeException("Tenant database already exists: {$dbName}");
        }

        $manager->createDatabase($tenant);

        $databaseManager = app(DatabaseManager::class);

        try {
            tenancy()->initialize($tenant);
            $databaseManager->connectToTenant($tenant);

            Artisan::call('migrate', [
                '--path' => database_path('migrations/tenant'),
                '--database' => 'tenant',
                '--realpath' => true,
                '--force' => true,
            ]);

            $user = User::query()->create([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
            ]);

            $role = Role::firstOrCreate([
                'name' => Roles::TENANT_ADMIN,
                'guard_name' => 'web',
            ]);
            $user->assignRole($role);
        } finally {
            $databaseManager->reconnectToCentral();
            tenancy()->end();
        }

        return $tenant;
    }
}
