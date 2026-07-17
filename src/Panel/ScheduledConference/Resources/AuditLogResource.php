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
            Tables\Columns\TextColumn::make('diagnostic_summary')
                ->label(__('ConferenceDiscountEligibility::messages.result'))
                ->state(static fn (ConferenceDiscountAuditLog $record): string => self::summary($record))
                ->wrap()
                ->limit(140)
                ->tooltip(static fn (ConferenceDiscountAuditLog $record): string => self::summary($record)),
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
                Infolists\Components\TextEntry::make('diagnostic_summary')
                    ->label(__('ConferenceDiscountEligibility::messages.result'))
                    ->state(static fn (ConferenceDiscountAuditLog $record): string => self::summary($record))
                    ->columnSpanFull(),
                Infolists\Components\TextEntry::make('old_values_pretty')
                    ->label(__('ConferenceDiscountEligibility::messages.old_values'))
                    ->state(static fn (ConferenceDiscountAuditLog $record): string => self::prettyJson($record->old_values))
                    ->copyable()
                    ->columnSpanFull(),
                Infolists\Components\TextEntry::make('new_values_pretty')
                    ->label(__('ConferenceDiscountEligibility::messages.new_values'))
                    ->state(static fn (ConferenceDiscountAuditLog $record): string => self::prettyJson($record->new_values))
                    ->copyable()
                    ->columnSpanFull(),
                Infolists\Components\TextEntry::make('context_pretty')
                    ->label(__('ConferenceDiscountEligibility::messages.context'))
                    ->state(static fn (ConferenceDiscountAuditLog $record): string => self::prettyJson($record->context))
                    ->copyable()
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('scheduled_conference_id', app()->getCurrentScheduledConference()?->getKey() ?? -1)
            ->with(['actor','affectedUser']);
    }

    public static function canViewAny(): bool { return app(Authorization::class)->canManage(); }
    public static function canView($record): bool
    {
        return app(Authorization::class)->canManage()
            && (int) $record->scheduled_conference_id === (int) app()->getCurrentScheduledConference()?->getKey();
    }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
    public static function getPages(): array { return ['index' => Pages\ListAuditLogs::route('/'), 'view' => Pages\ViewAuditLog::route('/{record}')]; }

    private static function prettyJson(mixed $value): string
    {
        if ($value === null || $value === []) {
            return '—';
        }

        $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : '—';
    }

    private static function summary(ConferenceDiscountAuditLog $record): string
    {
        if (in_array($record->action, ['discount_not_applied', 'payment_recalculated_without_discount'], true)) {
            $rules = is_array($record->context['evaluated_rules'] ?? null)
                ? $record->context['evaluated_rules']
                : [];
            $parts = [];
            foreach ($rules as $rule) {
                if (! is_array($rule)) {
                    continue;
                }
                $type = (string) ($rule['type'] ?? 'rule');
                $id = (string) ($rule['id'] ?? '?');
                $reason = (string) ($rule['rejection_reason'] ?? 'not_eligible');
                $parts[] = "{$type} #{$id}: {$reason}";
            }

            return $parts !== []
                ? implode('; ', $parts)
                : __('ConferenceDiscountEligibility::messages.no_matching_eligibility_rule');
        }

        if ($record->action === 'rule_payment_recalculation_completed') {
            $stats = is_array($record->new_values) ? $record->new_values : [];
            return __('ConferenceDiscountEligibility::messages.recalculation_summary', [
                'matched' => (int) ($stats['matched'] ?? 0),
                'discounted' => (int) ($stats['discounted'] ?? $stats['recalculated'] ?? 0),
                'unchanged' => (int) ($stats['unchanged'] ?? 0),
                'skipped' => (int) ($stats['skipped'] ?? 0),
                'paid' => (int) ($stats['paid'] ?? 0),
                'failed' => (int) ($stats['failed'] ?? 0),
                'unverified' => (int) ($stats['unverified_domain_matches'] ?? 0),
                'confirmed_authors' => (int) ($stats['confirmed_author_domain_matches'] ?? 0),
            ]);
        }

        if ($record->action === 'payment_recalculation_failed') {
            return __('ConferenceDiscountEligibility::messages.recalculation_failed_detail');
        }

        return '—';
    }
}
