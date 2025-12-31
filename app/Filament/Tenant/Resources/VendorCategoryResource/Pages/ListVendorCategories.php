<?php

namespace App\Filament\Tenant\Resources\VendorCategoryResource\Pages;

use App\Filament\Tenant\Resources\VendorCategoryResource;
use Filament\Resources\Pages\ListRecords;

class ListVendorCategories extends ListRecords
{
    protected static string $resource = VendorCategoryResource::class;
}
