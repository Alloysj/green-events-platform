<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\ReportResource\Pages\CreateReport;
use App\Filament\Tenant\Resources\ReportResource\Pages\EditReport;
use App\Filament\Tenant\Resources\ReportResource\Pages\ListReports;
use App\Jobs\GenerateReportJson;
use App\Jobs\GenerateReportPdf;
use App\Models\Domains\Events\Event;
use App\Models\Domains\Reporting\Report;
use App\Models\Domains\Reporting\ReportExport;
use App\Support\Roles;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $navigationGroup = 'Reporting';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Title')
                ->maxLength(255),
            Forms\Components\Select::make('event_id')
                ->label('Event')
                ->options(Event::query()->orderBy('id')->get()->mapWithKeys(
                    fn (Event $event): array => [
                        $event->id => ($event->name ? $event->name.' (#'.$event->id.')' : 'Event #'.$event->id),
                    ]
                ))
                ->searchable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Report::statusLabels()[$state] ?? 'Unknown'),
                Tables\Columns\TextColumn::make('exports_count')
                    ->counts('exports')
                    ->label('Exports'),
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
                Tables\Actions\Action::make('generatePdf')
                    ->label('Generate PDF')
                    ->visible(fn (): bool => self::canGenerate())
                    ->action(function (Report $record): void {
                        GenerateReportPdf::dispatchSync($record->id, auth()->id());
                    }),
                Tables\Actions\Action::make('exportJson')
                    ->label('Export JSON')
                    ->visible(fn (): bool => self::canGenerate())
                    ->action(function (Report $record): void {
                        GenerateReportJson::dispatchSync($record->id, auth()->id());
                    }),
                Tables\Actions\Action::make('downloadLatestPdf')
                    ->label('Download PDF')
                    ->visible(fn (Report $record): bool => self::latestExport($record, ReportExport::TYPE_PDF) !== null)
                    ->url(fn (Report $record): string => self::latestExport($record, ReportExport::TYPE_PDF)?->signedDownloadUrl() ?? '#')
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('downloadLatestJson')
                    ->label('Download JSON')
                    ->visible(fn (Report $record): bool => self::latestExport($record, ReportExport::TYPE_JSON) !== null)
                    ->url(fn (Report $record): string => self::latestExport($record, ReportExport::TYPE_JSON)?->signedDownloadUrl() ?? '#')
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
            'create' => CreateReport::route('/create'),
            'edit' => EditReport::route('/{record}/edit'),
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

    private static function canGenerate(): bool
    {
        $user = auth()->user();

        return $user?->hasAnyRole([Roles::TENANT_ADMIN, Roles::ESG_LEAD]) ?? false;
    }

    private static function latestExport(Report $report, string $type): ?ReportExport
    {
        return $report->exports()
            ->where('type', $type)
            ->where('status', ReportExport::STATUS_COMPLETE)
            ->orderByDesc('id')
            ->first();
    }
}




