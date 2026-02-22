<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\VendorResource\Pages\CreateVendor;
use App\Filament\Tenant\Resources\VendorResource\Pages\EditVendor;
use App\Filament\Tenant\Resources\VendorResource\Pages\ListVendors;
use App\Models\Domains\Vendors\Vendor;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

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
                ->options(fn (): array => \App\Models\Domains\Vendors\VendorCategory::query()->orderBy('name')->get()->mapWithKeys(fn ($c) => [$c->id => $c->name])->all())
                ->searchable()
                ->nullable()
                ->helperText('Optional: associate this vendor with a category.'),
            Forms\Components\TextInput::make('contact_name')
                ->label('Contact Name')
                ->maxLength(255)
                ->helperText('Optional'),
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(255)
                ->helperText('Optional, will be validated if present.'),
            Forms\Components\TextInput::make('phone')
                ->label('Phone')
                ->maxLength(50)
                ->helperText('Optional, include country code where possible.'),
            Forms\Components\Textarea::make('address')
                ->label('Address')
                ->rows(2)
                ->helperText('Optional'),
            Forms\Components\Textarea::make('notes')
                ->label('Notes')
                ->rows(3)
                ->helperText('Optional'),
            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),

            Forms\Components\Section::make('Sustainability / Compliance')
                ->schema([
                    Forms\Components\Repeater::make('complianceProfile')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('packaging_policy')
                                ->label('Packaging Policy')
                                ->options([
                                    'none' => 'None',
                                    'minimal' => 'Minimal',
                                    'recyclable' => 'Recyclable',
                                    'returnable' => 'Returnable',
                                ])
                                ->nullable(),
                            Forms\Components\Toggle::make('reusable_options')
                                ->label('Reusable Options')
                                ->helperText('Does the vendor offer reusable packaging or return systems?'),
                            Forms\Components\Textarea::make('reusable_options_notes')
                                ->label('Reusable Options Notes')
                                ->rows(2)
                                ->helperText('Optional details about reusable options'),
                            Forms\Components\Select::make('waste_handling_capability')
                                ->label('Waste Handling')
                                ->options([
                                    'none' => 'None',
                                    'basic' => 'Basic Collection',
                                    'onsite' => 'On-site Processing',
                                    'offsite' => 'Off-site Processing',
                                ])
                                ->nullable(),
                            Forms\Components\TextInput::make('distance_km')
                                ->label('Distance (km)')
                                ->numeric(),
                            Forms\Components\Repeater::make('certifications')
                                ->label('Certifications')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Certification')
                                        ->required(),
                                ])
                                ->defaultItems(0)
                                ->columns(1),
                            Forms\Components\KeyValue::make('additional_metadata')
                                ->label('Additional Metadata')
                                ->helperText('Arbitrary JSON metadata for the compliance profile'),
                        ])
                        ->minItems(0)
                        ->maxItems(1)
                        ->columnSpan('full'),
                ])
                ->columns(1)
                ->columnSpan('full'),
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
                Tables\Columns\BadgeColumn::make('approval_status')
                    ->label('Approval')
                    ->formatStateUsing(function (Vendor $record): string {
                        if ($record->isApproved()) {
                            return 'Approved';
                        }

                        if ($record->isExpired()) {
                            return 'Expired';
                        }

                        if ($record->needsReverification()) {
                            return 'Needs Reverification';
                        }

                        return 'Not Approved';
                    })
                    ->colors([
                        'success' => 'Approved',
                        'warning' => 'Needs Reverification',
                        'danger' => 'Expired',
                        'secondary' => 'Not Approved',
                    ]),
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
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
->visible(fn (Vendor $record): bool => auth()->user()?->can('VENDORS:APPROVE') || auth()->user()?->hasRole(\App\Support\Roles::TENANT_ADMIN))
                    ->form([
                        Forms\Components\DatePicker::make('expires_at')
                            ->label('Expires At')
                            ->minDate(now()),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ])
                    ->action(function (Vendor $record, array $data): void {
                        $user = auth()->user();
                        if (! $user) {
                            return;
                        }

                        $record->approveBy($user, $data['expires_at'] ?? null, $data['notes'] ?? null);
                    }),
                Tables\Actions\Action::make('revoke')
                    ->label('Revoke')
                    ->color('danger')
                    ->requiresConfirmation()
->visible(fn (Vendor $record): bool => auth()->user()?->can('VENDORS:APPROVE') || auth()->user()?->hasRole(\App\Support\Roles::TENANT_ADMIN))
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ])
                    ->action(function (Vendor $record, array $data): void {
                        $user = auth()->user();
                        if (! $user) {
                            return;
                        }

                        $record->revokeBy($user, $data['notes'] ?? null);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->filters([
                Tables\Filters\Filter::make('approved')
                    ->label('Approved')
                    ->query(fn ($q) => $q->whereHas('approvals', function ($q2) {
                        $q2->where('status', \App\Models\Domains\Vendors\VendorApproval::STATUS_APPROVED)
                            ->where(function ($q3) {
                                $q3->whereNull('expires_at')->orWhere('expires_at', '>', now());
                            });
                    })),
                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn ($q) => $q->whereHas('approvals', function ($q2) {
                        $q2->where('status', \App\Models\Domains\Vendors\VendorApproval::STATUS_APPROVED)
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '<=', now());
                    })),
                Tables\Filters\Filter::make('reverification')
                    ->label('Needs Reverification')
                    ->query(fn ($q) => $q->whereHas('approvals', function ($q2) {
                        $q2->where('status', \App\Models\Domains\Vendors\VendorApproval::STATUS_APPROVED)
                            ->whereNotNull('expires_at')
                            ->whereBetween('expires_at', [now(), now()->addDays(config('vendors.reverification_days', 30))]);
                    })),
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




