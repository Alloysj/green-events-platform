<?php

namespace App\Filament\System\Resources;

use App\Filament\System\Resources\TenantResource\Pages\CreateTenant;
use App\Filament\System\Resources\TenantResource\Pages\ListTenants;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Tenants';

    public static function form(Form $form): Form
    {
        return $form->schema([
                Forms\Components\TextInput::make('id')
                    ->label('Tenant ID')
                    ->required()
                    ->maxLength(64),
                Forms\Components\TextInput::make('domain')
                    ->label('Primary Domain')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('database')
                    ->label('Database Name')
                    ->maxLength(128),
                Forms\Components\TextInput::make('admin_name')
                    ->label('Admin Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('admin_email')
                    ->label('Admin Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('admin_password')
                    ->label('Admin Password')
                    ->password()
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Tenant ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('domains.domain')
                    ->label('Domain')
                    ->listWithLineBreaks(),
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
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->hasAnyRole(\App\Support\Roles::systemRoles()) ?? false;
    }
}




