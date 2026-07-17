<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources;

use ConferenceDiscountEligibility\Models\ConferenceDiscountAuditLog;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\AuditLogResource\Pages;
use ConferenceDiscountEligibility\Services\Authorization;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class AuditLogResource extends Resource
{
    protected static ?string $model = ConferenceDiscountAuditLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 60;

    public static function getNavigationGroup(): ?string { return __('ConferenceDiscountEligibility::messages.navigation_group'); }
    public static function getNavigationLabel(): string { return __('ConferenceDiscountEligibility::messages.audit_log'); }
    public static function getModelLabel(): string { return __('ConferenceDiscountEligibility::messages.audit_entry'); }
    public static function getPluralModelLabel(): string { return __('ConferenceDiscountEligibility::messages.audit_log'); }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->label(__('ConferenceDiscountEligibility::messages.date'))->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('action')->label(__('ConferenceDiscountEligibility::messages.action'))->badge()->searchable(),
            Tables\Columns\TextColumn::make('actor.email')->label(__('ConferenceDiscountEligibility::messages.administrator'))->placeholder(__('ConferenceDiscountEligibility::messages.system')),
            Tables\Columns\TextColumn::make('affectedUser.email')->label(__('ConferenceDiscountEligibility::messages.affected_user'))->placeholder('—'),
            Tables\Columns\TextColumn::make('auditable_type')->label(__('ConferenceDiscountEligibility::messages.object'))->formatStateUsing(fn ($state) => class_basename((string) $state))->placeholder('—'),
            Tables\Columns\TextColumn::make('origin')->label(__('ConferenceDiscountEligibility::messages.origin'))->badge(),
        ])->filters([
            Tables\Filters\SelectFilter::make('action')->options(fn () => ConferenceDiscountAuditLog::query()->where('scheduled_conference_id', app()->getCurrentScheduledConference()?->getKey())->distinct()->orderBy('action')->pluck('action','action')->all()),
        ])->actions([Tables\Actions\ViewAction::make()])->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make(__('ConferenceDiscountEligibility::messages.audit_entry'))->schema([
                Infolists\Components\TextEntry::make('created_at')->dateTime(),
                Infolists\Components\TextEntry::make('action'),
                Infolists\Components\TextEntry::make('actor.email')->placeholder(__('ConferenceDiscountEligibility::messages.system')),
                Infolists\Components\TextEntry::make('affectedUser.email')->placeholder('—'),
                Infolists\Components\TextEntry::make('origin'),
                Infolists\Components\TextEntry::make('ip_hash')->placeholder('—')->copyable(),
                Infolists\Components\TextEntry::make('old_values')->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))->columnSpanFull(),
                Infolists\Components\TextEntry::make('new_values')->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))->columnSpanFull(),
                Infolists\Components\TextEntry::make('context')->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('scheduled_conference_id', app()->getCurrentScheduledConference()?->getKey() ?? -1)->with(['actor','affectedUser']);
    }

    public static function canViewAny(): bool { return app(Authorization::class)->canManage(); }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
    public static function getPages(): array { return ['index' => Pages\ListAuditLogs::route('/'), 'view' => Pages\ViewAuditLog::route('/{record}')]; }
}
