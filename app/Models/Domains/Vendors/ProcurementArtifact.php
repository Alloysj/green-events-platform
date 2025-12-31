<?php

namespace App\Models\Domains\Vendors;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class ProcurementArtifact extends Model
{
    use TenantConnection;
}

