<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    /**
     * Convenience wrapper to create the tenant database using the configured manager.
     */
    public function createDatabase(): void
    {
        $manager = $this->database()->manager();

        try {
            $dbName = $this->database()->getName();

            // For Postgres (and other managed drivers), drop the existing database if the manager supports it.
            // We avoid filesystem-specific behavior here since tenants use a managed DB (Postgres) in this project.
            if (method_exists($manager, 'databaseExists') && $manager->databaseExists($dbName)) {
                if (method_exists($manager, 'dropDatabase')) {
                    $manager->dropDatabase($this);
                }
            }
        } catch (\Throwable $e) {
            // ignore and proceed to create
        }

        $manager->createDatabase($this);
    }
}
