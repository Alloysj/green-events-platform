<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\EventResource\Pages\CreateEvent;
use App\Filament\Tenant\Resources\EventResource\Pages\EditEvent;
use App\Filament\Tenant\Resources\EventResource\Pages\ListEvents;
use App\Models\Domains\Events\Event;
use App\Services\EventLifecycle;
use App\Support\Roles;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Events';

    protected static ?string $navigationGroup = 'Operations';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Event Name')
                ->maxLength(255),
            Forms\Components\Toggle::make('requires_risk_approval')
                ->label('Requires Risk/Compliance Approval'),
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
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Event::statusLabels()[$state] ?? 'Unknown')
                    ->color(fn (?string $state): string => match ($state) {
                        Event::STATUS_DRAFT => 'gray',
                        Event::STATUS_PENDING_APPROVAL => 'warning',
                        Event::STATUS_APPROVED => 'success',
                        Event::STATUS_COMPLETED => 'primary',
                        Event::STATUS_REPORT_PUBLISHED => 'success',
                        default => 'gray',
                    }),
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
                Tables\Actions\Action::make('requestApproval')
                    ->label('Request Approval')
                    ->visible(fn (Event $record): bool => self::canRequestApproval($record))
                    ->requiresConfirmation()
                    ->action(function (Event $record): void {
                        $user = auth()->user();
                        if (! $user) {
                            return;
                        }

                        app(EventLifecycle::class)->requestApproval($record, $user);
                    }),
                Tables\Actions\Action::make('recordApproval')
                    ->label('Record Approval')
                    ->visible(fn (Event $record): bool => self::canRecordApproval($record))
                    ->form(function (Event $record): array {
                        return [
                            Forms\Components\Select::make('type')
                                ->label('Approval Type')
                                ->options(self::approvalTypeOptions($record))
                                ->required(),
                            Forms\Components\Radio::make('decision')
                                ->label('Decision')
                                ->options([
                                    'approve' => 'Approve',
                                    'reject' => 'Reject',
                                ])
                                ->required(),
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->rows(3),
                        ];
                    })
                    ->action(function (Event $record, array $data): void {
                        $user = auth()->user();
                        if (! $user) {
                            return;
                        }

                        $approved = $data['decision'] === 'approve';

                        app(EventLifecycle::class)->recordApproval(
                            $record,
                            $user,
                            $data['type'],
                            $approved,
                            $data['notes'] ?? null
                        );
                    }),
                Tables\Actions\Action::make('complete')
                    ->label('Complete Event')
                    ->visible(fn (Event $record): bool => self::canComplete($record))
                    ->requiresConfirmation()
                    ->action(function (Event $record): void {
                        $user = auth()->user();
                        if (! $user) {
                            return;
                        }

                        app(EventLifecycle::class)->complete($record, $user);
                    }),
                Tables\Actions\Action::make('publishReport')
                    ->label('Publish Report')
                    ->visible(fn (Event $record): bool => self::canPublishReport($record))
                    ->requiresConfirmation()
                    ->action(function (Event $record): void {
                        $user = auth()->user();
                        if (! $user) {
                            return;
                        }

                        app(EventLifecycle::class)->publishReport($record, $user);
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    private static function canRequestApproval(Event $record): bool
    {
        $user = auth()->user();

        return $user
            && $record->status === Event::STATUS_DRAFT
            && $user->hasAnyRole([Roles::TENANT_ADMIN, Roles::EVENT_OWNER]);
    }

    private static function canRecordApproval(Event $record): bool
    {
        if ($record->status !== Event::STATUS_PENDING_APPROVAL) {
            return false;
        }

        return count(self::approvalTypeOptions($record)) > 0;
    }

    private static function canComplete(Event $record): bool
    {
        $user = auth()->user();

        return $user
            && $record->status === Event::STATUS_APPROVED
            && $user->hasAnyRole([Roles::TENANT_ADMIN, Roles::EVENT_OWNER]);
    }

    private static function canPublishReport(Event $record): bool
    {
        $user = auth()->user();

        return $user
            && $record->status === Event::STATUS_COMPLETED
            && $user->hasAnyRole([Roles::TENANT_ADMIN, Roles::ESG_LEAD]);
    }

    private static function approvalTypeOptions(Event $record): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $options = [
            Event::APPROVAL_ESG => 'ESG Approval',
            Event::APPROVAL_PROCUREMENT => 'Procurement Approval',
        ];

        if ($record->requires_risk_approval) {
            $options[Event::APPROVAL_RISK] = 'Risk/Compliance Approval';
        }

        if ($user->hasRole(Roles::TENANT_ADMIN)) {
            return $options;
        }

        $allowed = [];
        if ($user->hasRole(Roles::ESG_LEAD)) {
            $allowed[Event::APPROVAL_ESG] = $options[Event::APPROVAL_ESG];
        }

        if ($user->hasRole(Roles::PROCUREMENT)) {
            $allowed[Event::APPROVAL_PROCUREMENT] = $options[Event::APPROVAL_PROCUREMENT];
        }

        if ($record->requires_risk_approval && $user->hasRole(Roles::RISK_REVIEWER)) {
            $allowed[Event::APPROVAL_RISK] = $options[Event::APPROVAL_RISK];
        }

        return $allowed;
    }
}




