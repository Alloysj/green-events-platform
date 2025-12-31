<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\EvidenceArtifactResource\Pages\CreateEvidenceArtifact;
use App\Filament\Tenant\Resources\EvidenceArtifactResource\Pages\EditEvidenceArtifact;
use App\Filament\Tenant\Resources\EvidenceArtifactResource\Pages\ListEvidenceArtifacts;
use App\Models\Domains\Events\Event;
use App\Models\Domains\Evidence\EvidenceArtifact;
use App\Models\Domains\Reporting\Report;
use App\Models\Domains\Vendors\ProcurementArtifact;
use App\Models\Domains\Vendors\Vendor;
use App\Models\Domains\Waste\WastePlan;
use App\Support\Roles;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class EvidenceArtifactResource extends Resource
{
    protected static ?string $model = EvidenceArtifact::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?string $navigationLabel = 'Evidence Artifacts';

    protected static ?string $navigationGroup = 'Evidence';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('File')
                ->schema([
                    Forms\Components\FileUpload::make('storage_path')
                        ->label('File')
                        ->disk(self::evidenceDisk())
                        ->directory('evidence')
                        ->storeFileNamesIn('file_name')
                        ->preserveFilenames()
                        ->required(fn (string $context): bool => $context === 'create'),
                ]),
            Forms\Components\Section::make('Links')
                ->schema([
                    Forms\Components\Repeater::make('links')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('linkable_type')
                                ->label('Link Type')
                                ->options(self::linkableTypeOptions())
                                ->required()
                                ->reactive(),
                            Forms\Components\Select::make('linkable_id')
                                ->label('Linked Record')
                                ->options(fn (Get $get): array => self::linkableOptions($get('linkable_type')))
                                ->required()
                                ->searchable(),
                            Forms\Components\TextInput::make('report_section_tag')
                                ->label('Report Section Tag')
                                ->visible(fn (Get $get): bool => $get('linkable_type') === Report::class)
                                ->maxLength(255),
                        ])
                        ->defaultItems(0)
                        ->columns(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('file_name')
                    ->label('File')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn (?int $state): string => self::formatSize($state)),
                Tables\Columns\TextColumn::make('links_count')
                    ->counts('links')
                    ->label('Links'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->label('Created'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->label('Updated'),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->url(fn (EvidenceArtifact $record): string => $record->signedDownloadUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (): bool => self::canAccess()),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvidenceArtifacts::route('/'),
            'create' => CreateEvidenceArtifact::route('/create'),
            'edit' => EditEvidenceArtifact::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return self::canAccess();
    }

    public static function canCreate(): bool
    {
        return self::canAccess();
    }

    public static function canEdit($record): bool
    {
        return self::canAccess();
    }

    public static function canDelete($record): bool
    {
        return self::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (Role::query()->count() === 0) {
            return true;
        }

        return $user->hasAnyRole(Roles::tenantRoles());
    }

    private static function evidenceDisk(): string
    {
        return config('evidence.disk', 'local');
    }

    private static function linkableTypeOptions(): array
    {
        return [
            Event::class => 'Event',
            Vendor::class => 'Vendor',
            ProcurementArtifact::class => 'Procurement Artifact',
            WastePlan::class => 'Waste Plan',
            Report::class => 'Report',
        ];
    }

    private static function linkableOptions(?string $type): array
    {
        if (! $type) {
            return [];
        }

        $records = match ($type) {
            Event::class => Event::query()->orderBy('id')->get()->mapWithKeys(
                fn (Event $event): array => [
                    $event->id => ($event->name ? $event->name.' (#'.$event->id.')' : 'Event #'.$event->id),
                ]
            ),
            Vendor::class => Vendor::query()->orderBy('id')->get()->mapWithKeys(
                fn (Vendor $vendor): array => [$vendor->id => 'Vendor #'.$vendor->id]
            ),
            ProcurementArtifact::class => ProcurementArtifact::query()->orderBy('id')->get()->mapWithKeys(
                fn (ProcurementArtifact $artifact): array => [$artifact->id => 'Procurement Artifact #'.$artifact->id]
            ),
            WastePlan::class => WastePlan::query()->orderBy('id')->get()->mapWithKeys(
                fn (WastePlan $plan): array => [$plan->id => 'Waste Plan #'.$plan->id]
            ),
            Report::class => Report::query()->orderBy('id')->get()->mapWithKeys(
                fn (Report $report): array => [$report->id => 'Report #'.$report->id]
            ),
            default => collect(),
        };

        return $records->all();
    }

    private static function formatSize(?int $bytes): string
    {
        if (! $bytes) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) $bytes;
        $index = 0;

        while ($size >= 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        return number_format($size, 2).' '.$units[$index];
    }
}




