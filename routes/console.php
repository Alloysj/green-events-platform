<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\TenantProvisioner;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('system:migrate', function () {
    $this->call('migrate', [
        '--path' => database_path('migrations/system'),
        '--realpath' => true,
        '--force' => true,
    ]);
})->purpose('Run system database migrations');

Artisan::command('tenant:provision {tenantId} {domain} {--admin-name=Admin} {--admin-email=admin@example.com} {--admin-password=password} {--database=}', function () {
    $tenant = app(TenantProvisioner::class)->provision(
        $this->argument('tenantId'),
        $this->argument('domain'),
        (string) $this->option('admin-name'),
        (string) $this->option('admin-email'),
        (string) $this->option('admin-password'),
        $this->option('database') ?: null,
    );

    $this->info('Provisioned tenant '.$tenant->id);
})->purpose('Create tenant record, database, migrations, and admin user');
