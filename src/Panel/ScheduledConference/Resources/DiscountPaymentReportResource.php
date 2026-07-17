<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources;

use ConferenceDiscountEligibility\Models\ConferenceDiscountPaymentSnapshot;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\DiscountPaymentReportResource\Pages;
use ConferenceDiscountEligibility\Services\Authorization;
use ConferenceDiscountEligibility\Support\Money;
use ConferenceDiscountEligibility\Support\Percentage;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class DiscountPaymentReportResource extends Resource
{
    protected static ?string $model = ConferenceDiscountPaymentSnapshot::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?int $navigationSort = 70;

    public static function getNavigationGroup(): ?string { return __('ConferenceDiscountEligibility::messages.navigation_group'); }
    public static function getNavigationLabel(): string { return __('ConferenceDiscountEligibility::messages.discount_payment_report'); }
    public static function getModelLabel(): string { return __('ConferenceDiscountEligibility::messages.discount_payment'); }
    public static function getPluralModelLabel(): string { return __('ConferenceDiscountEligibility::messages.discount_payment_report'); }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('payment.invoice')->label(__('ConferenceDiscountEligibility::messages.invoice'))->placeholder('—')->searchable(),
            Tables\Columns\TextColumn::make('user.email')->label(__('ConferenceDiscountEligibility::messages.email'))->searchable(),
            Tables\Columns\TextColumn::make('original_total_minor')->label(__('ConferenceDiscountEligibility::messages.standard_total'))->formatStateUsing(fn ($state, $record) => static::formatMoney((int) $state, $record->currency))->sortable(),
            Tables\Columns\TextColumn::make('discount_percentage_basis_points')->label(__('ConferenceDiscountEligibility::messages.discount'))->formatStateUsing(fn ($state) => Percentage::format((int) $state) . '%')->sortable(),
            Tables\Columns\TextColumn::make('discount_amount_minor')->label(__('ConferenceDiscountEligibility::messages.discount_amount'))->formatStateUsing(fn ($state, $record) => static::formatMoney(-((int) $state), $record->currency))->sortable(),
            Tables\Columns\TextColumn::make('final_total_minor')->label(__('ConferenceDiscountEligibility::messages.final_total'))->formatStateUsing(fn ($state, $record) => static::formatMoney((int) $state, $record->currency))->sortable(),
            Tables\Columns\TextColumn::make('eligibility_reason')->label(__('ConferenceDiscountEligibility::messages.reason'))->limit(35),
            Tables\Columns\TextColumn::make('eligibility_type')->label(__('ConferenceDiscountEligibility::messages.origin'))->badge(),
            Tables\Columns\TextColumn::make('payment.payment_method')->label(__('ConferenceDiscountEligibility::messages.payment_method'))->placeholder('—')->badge(),
            Tables\Columns\TextColumn::make('status')->label(__('ConferenceDiscountEligibility::messages.status'))->state(fn ($record) => $record->payment?->isPaid() ? __('ConferenceDiscountEligibility::messages.paid') : __('ConferenceDiscountEligibility::messages.unpaid'))->badge()->color(fn ($state) => $state === __('ConferenceDiscountEligibility::messages.paid') ? 'success' : 'warning'),
            Tables\Columns\TextColumn::make('paypal_id')->label(__('ConferenceDiscountEligibility::messages.paypal_payment_id'))->state(fn ($record) => $record->payment?->getMeta('paypal_payment_id'))->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
        ])->actions([
            Tables\Actions\Action::make('payment')->label(__('ConferenceDiscountEligibility::messages.view_payment'))->icon('heroicon-o-arrow-top-right-on-square')->url(fn ($record) => $record->payment?->getPaymentDetailUrl())->openUrlInNewTab(),
            Tables\Actions\ViewAction::make(),
        ])->filters([
            Tables\Filters\SelectFilter::make('eligibility_type')->options(['user' => __('ConferenceDiscountEligibility::messages.type_user'), 'email' => __('ConferenceDiscountEligibility::messages.type_email'), 'domain' => __('ConferenceDiscountEligibility::messages.type_domain')]),
        ])->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make(__('ConferenceDiscountEligibility::messages.discount_details'))->schema([
                Infolists\Components\TextEntry::make('user.email')->label(__('ConferenceDiscountEligibility::messages.email')),
                Infolists\Components\TextEntry::make('eligibility_type')->label(__('ConferenceDiscountEligibility::messages.origin')),
                Infolists\Components\TextEntry::make('eligibility_reason')->label(__('ConferenceDiscountEligibility::messages.reason')),
                Infolists\Components\TextEntry::make('discount_percentage_basis_points')->label(__('ConferenceDiscountEligibility::messages.discount'))->formatStateUsing(fn ($state) => Percentage::format((int) $state) . '%'),
                Infolists\Components\TextEntry::make('original_base_amount_minor')->label(__('ConferenceDiscountEligibility::messages.standard_fee'))->formatStateUsing(fn ($state, $record) => static::formatMoney((int) $state, $record->currency)),
                Infolists\Components\TextEntry::make('discount_amount_minor')->label(__('ConferenceDiscountEligibility::messages.discount_amount'))->formatStateUsing(fn ($state, $record) => static::formatMoney(-((int) $state), $record->currency)),
                Infolists\Components\TextEntry::make('add_on_amount_minor')->label(__('ConferenceDiscountEligibility::messages.add_ons'))->formatStateUsing(fn ($state, $record) => static::formatMoney((int) $state, $record->currency)),
                Infolists\Components\TextEntry::make('final_total_minor')->label(__('ConferenceDiscountEligibility::messages.final_total'))->formatStateUsing(fn ($state, $record) => static::formatMoney((int) $state, $record->currency)),
                Infolists\Components\TextEntry::make('payment.payment_method')->label(__('ConferenceDiscountEligibility::messages.payment_method'))->placeholder('—'),
                Infolists\Components\TextEntry::make('paypal_id')->label(__('ConferenceDiscountEligibility::messages.paypal_payment_id'))->state(fn ($record) => $record->payment?->getMeta('paypal_payment_id'))->placeholder('—'),
                Infolists\Components\TextEntry::make('eligibility_snapshot_at')->label(__('ConferenceDiscountEligibility::messages.snapshot_at'))->dateTime(),
                Infolists\Components\TextEntry::make('metadata')->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('scheduled_conference_id', app()->getCurrentScheduledConference()?->getKey() ?? -1)->with(['payment','user']);
    }

    public static function formatMoney(int $minor, string $currency): string
    {
        return money((float) Money::decimal($minor, $currency), $currency, true)->formatWithoutZeroes();
    }

    public static function canViewAny(): bool { return app(Authorization::class)->canManage(); }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
    public static function getPages(): array { return ['index' => Pages\ListDiscountPayments::route('/'), 'view' => Pages\ViewDiscountPayment::route('/{record}')]; }
}
