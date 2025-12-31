<?php

namespace App\Models\Domains\Waste;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class WastePlan extends Model
{
    use TenantConnection;
}

