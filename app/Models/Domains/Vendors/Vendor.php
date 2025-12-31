<?php

namespace App\Models\Domains\Vendors;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class Vendor extends Model
{
    use TenantConnection;

    protected $fillable = [
        'name',
        'category_id',
        'contact_name',
        'email',
        'phone',
        'address',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(VendorCategory::class, 'category_id');
    }
}

