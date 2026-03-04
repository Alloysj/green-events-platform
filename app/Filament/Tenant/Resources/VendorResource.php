<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\VendorResource\Pages\CreateVendor;
use App\Filament\Tenant\Resources\VendorResource\Pages\EditVendor;
use App\Filament\Tenant\Resources\VendorResource\Pages\ListVendors;
use App\Models\Domains\Vendors\VendorCategory;
use App\Models\Domains\Vendors\Vendor;
use App\Support\Roles;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Vendors';

    protected static ?string $navigationGroup = 'Operations';


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('category_id')
                ->label('Category')
                ->relationship('category', 'name')
                ->searchable()
                ->nullable()
                ->preload(),
            Forms\Components\TextInput::make('contact_name')
                ->label('Contact Name')
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(255),
            Forms\Components\TextInput::make('phone')
                ->label('Phone')
                ->maxLength(50),
            Forms\Components\Textarea::make('address')
                ->label('Address')
                ->rows(2),
            Forms\Components\Textarea::make('notes')
                ->label('Notes')
                ->rows(3),
            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),
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
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->limit(30),
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contact')
                    ->limit(40),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->limit(40),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->options([true => 'heroicon-o-check', false => 'heroicon-o-x'])
                    ->colors([true => 'success', false => 'danger']),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->label('Created'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
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

    public static function canAccess(bool $write = false): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (\Spatie\Permission\Models\Permission::query()->count() === 0) {
            return $user->hasAnyRole(\App\Support\Roles::tenantRoles());
        }

        $permission = $write ? 'VENDORS:WRITE' : 'VENDORS:READ';

        return $user->can($permission);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendors::route('/'),
            'create' => CreateVendor::route('/create'),
            'edit' => EditVendor::route('/{record}/edit'),
        ];
    }
}



