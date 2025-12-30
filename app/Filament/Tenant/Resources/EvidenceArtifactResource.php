<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\EvidenceArtifactResource\Pages\CreateEvidenceArtifact;
use App\Filament\Tenant\Resources\EvidenceArtifactResource\Pages\EditEvidenceArtifact;
use App\Filament\Tenant\Resources\EvidenceArtifactResource\Pages\ListEvidenceArtifacts;
use App\Models\Domains\Evidence\EvidenceArtifact;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class EvidenceArtifactResource extends Resource
{
    protected static ?string $model = EvidenceArtifact::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?string $navigationLabel = 'Evidence Artifacts';

    public static function form(Form $form): Form
    {
        return $form->schema([
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
            'index' => ListEvidenceArtifacts::route('/'),
            'create' => CreateEvidenceArtifact::route('/create'),
            'edit' => EditEvidenceArtifact::route('/{record}/edit'),
        ];
    }
}




