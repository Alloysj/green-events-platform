<?php

namespace App\Models\Domains\Vendors;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class VendorComplianceProfile extends Model
{
    use TenantConnection;

    protected $table = 'vendor_compliance_profiles';

    protected $fillable = [
        'vendor_id',
        'packaging_policy',
        'reusable_options',
        'reusable_options_notes',
        'waste_handling_capability',
        'distance_km',
        'certifications',
        'additional_metadata',
    ];

    protected $casts = [
        'reusable_options' => 'boolean',
        'distance_km' => 'decimal:2',
        'certifications' => 'array',
        'additional_metadata' => 'array',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}
