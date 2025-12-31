<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\VendorCategoryResource\Pages\CreateVendorCategory;
use App\Filament\Tenant\Resources\VendorCategoryResource\Pages\EditVendorCategory;
use App\Filament\Tenant\Resources\VendorCategoryResource\Pages\ListVendorCategories;
use App\Models\Domains\Vendors\VendorCategory;
use App\Support\Roles;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class VendorCategoryResource extends Resource
{
    protected static ?string $model = VendorCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationLabel = 'Vendor Categories';

    protected static ?string $navigationGroup = 'Operations';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255)
                ->reactive()
                ->afterStateUpdated(function (?string $state, callable $set) {
                    if ($state === null) {
                        return;
                    }

                    // Only auto-fill slug when it's empty to avoid overriding manual edits
                    $set('slug', Str::slug($state));
                }),
            Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(255)
                ->helperText('Unique URL-friendly identifier. Auto-generated from Name but editable.'),
            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('vendors_count')
                    ->counts('vendors')
                    ->label('Vendors'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->label('Created'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorCategories::route('/'),
            'create' => CreateVendorCategory::route('/create'),
            'edit' => EditVendorCategory::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return self::canAccess(false);
    }

    public static function canCreate(): bool
    {
        return self::canAccess(true);
    }

    public static function canEdit($record): bool
    {
        return self::canAccess(true);
    }

    public static function canDelete($record): bool
    {
        return self::canAccess(true);
    }

    private static function canAccess(bool $write = false): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (Permission::query()->count() === 0) {
            return $user->hasAnyRole(Roles::tenantRoles());
        }

        $permission = $write ? 'VENDORS:WRITE' : 'VENDORS:READ';

        return $user->can($permission);
    }
}
