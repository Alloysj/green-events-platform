<?php

use App\Models\Domain;
use App\Models\Tenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;

return [
    'tenant_model' => Tenant::class,
    'domain_model' => Domain::class,

    'central_domains' => array_filter(explode(',', (string) env('TENANCY_CENTRAL_DOMAINS', 'localhost'))),

    'bootstrappers' => [
        DatabaseTenancyBootstrapper::class,
    ],

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'system'),
        'tenant_connection' => 'tenant',
        'template_tenant_connection' => env('TENANT_DB_TEMPLATE', env('DB_CONNECTION', 'pgsql')),
        'managers' => [
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'mariadb' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
        ],
    ],

    'migration_parameters' => [
        '--path' => database_path('migrations/tenant'),
        '--database' => 'tenant',
        '--realpath' => true,
    ],
];
