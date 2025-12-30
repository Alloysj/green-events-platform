<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\ReportResource\Pages\CreateReport;
use App\Filament\Tenant\Resources\ReportResource\Pages\EditReport;
use App\Filament\Tenant\Resources\ReportResource\Pages\ListReports;
use App\Models\Domains\Reporting\Report;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Reports';

    public static function form(Form $form): Form
    {
        return $form->schema([
                Placeholder::make('placeholder')
                    ->label('Report Fields')
                    ->content('Report fields will be added in a later milestone.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->label('Created'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->label('Updated'),
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
}




