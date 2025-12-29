<?php

namespace App\Filament\System\Resources\TenantResource\Pages;

use App\Filament\System\Resources\TenantResource;
use App\Services\TenantProvisioner;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(TenantProvisioner::class)->provision(
            $data['id'],
            $data['domain'],
            $data['admin_name'],
            $data['admin_email'],
            $data['admin_password'],
            $data['database'] ?: null,
        );
    }
}
