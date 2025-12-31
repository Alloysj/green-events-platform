<?php

namespace App\Models\Domains\Vendors;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class VendorCategory extends Model
{
    use TenantConnection;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function vendors()
    {
        return $this->hasMany(Vendor::class, 'category_id');
    }

    protected static function booted(): void
    {
        static::creating(function (VendorCategory $category): void {
            $category->generateSlugIfMissing();
        });

        static::updating(function (VendorCategory $category): void {
            // ensure slug is present and unique if changed or missing
            $category->generateSlugIfMissing();
        });
    }

    private function generateSlugIfMissing(): void
    {
        if (! empty($this->slug)) {
            $this->slug = \Illuminate\Support\Str::slug($this->slug);
        } elseif (! empty($this->name)) {
            $this->slug = \Illuminate\Support\Str::slug($this->name);
        } else {
            return;
        }

        // ensure uniqueness by appending -n suffix when necessary
        $original = $this->slug;
        $i = 1;

        while (self::query()->where('slug', $this->slug)->when($this->exists, fn ($q) => $q->where('id', '!=', $this->id))->exists()) {
            $this->slug = $original.'-'.$i;
            $i++;
        }
    }
}
