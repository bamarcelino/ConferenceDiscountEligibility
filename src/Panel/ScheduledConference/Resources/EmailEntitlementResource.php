<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources;

use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Models\ConferenceDiscountEntitlement;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\EmailEntitlementResource\Pages;
use ConferenceDiscountEligibility\Services\Authorization;
use ConferenceDiscountEligibility\Services\RecalculationCoordinator;
use ConferenceDiscountEligibility\Services\SettingsRepository;
use ConferenceDiscountEligibility\Support\Percentage;
use ConferenceDiscountEligibility\Support\RecalculationFeedback;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class EmailEntitlementResource extends Resource
{
    protected static ?string $model = ConferenceDiscountEntitlement::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string { return __('ConferenceDiscountEligibility::messages.navigation_group'); }
    public static function getNavigationLabel(): string { return __('ConferenceDiscountEligibility::messages.email_lists'); }
    public static function getModelLabel(): string { return __('ConferenceDiscountEligibility::messages.email_entitlement'); }
    public static function getPluralModelLabel(): string { return __('ConferenceDiscountEligibility::messages.email_lists'); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('original_email')->label(__('ConferenceDiscountEligibility::messages.email'))->email()->required()->maxLength(255),
            Forms\Components\TextInput::make('percentage')->label(__('ConferenceDiscountEligibility::messages.percentage'))->numeric()->minValue(0.01)->maxValue(100)->step(0.01)->datalist(['40','30'])->suffix('%')->required(),
            Forms\Components\TextInput::make('reason')->label(__('ConferenceDiscountEligibility::messages.reason'))->datalist(IndividualEntitlementResource::reasonPresets())->required()->maxLength(255),
            Forms\Components\DateTimePicker::make('valid_from')->label(__('ConferenceDiscountEligibility::messages.valid_from'))->native(false),
            Forms\Components\DateTimePicker::make('valid_until')->label(__('ConferenceDiscountEligibility::messages.valid_until'))->native(false)->afterOrEqual('valid_from'),
            Forms\Components\Toggle::make('active')->label(__('ConferenceDiscountEligibility::messages.active'))->default(true),
            Forms\Components\TextInput::make('maximum_uses')->label(__('ConferenceDiscountEligibility::messages.maximum_uses'))->numeric()->integer()->minValue(1),
            Forms\Components\Textarea::make('notes')->label(__('ConferenceDiscountEligibility::messages.notes'))->maxLength(5000)->columnSpanFull(),
            Forms\Components\Toggle::make('recalculate_unpaid_payments')->label(__('ConferenceDiscountEligibility::messages.recalculate_eligible_unpaid_payments'))->helperText(__('ConferenceDiscountEligibility::messages.recalculate_warning'))->default(fn () => app(SettingsRepository::class)->forConference((int) app()->getCurrentScheduledConference()?->getKey())->recalculate_unpaid_default),
            Forms\Components\Toggle::make('notify_on_recalculation')->label(__('ConferenceDiscountEligibility::messages.notify_user'))->default(fn () => app(SettingsRepository::class)->forConference((int) app()->getCurrentScheduledConference()?->getKey())->notify_on_recalculation),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('original_email')->label(__('ConferenceDiscountEligibility::messages.email'))->searchable()->copyable(),
            Tables\Columns\TextColumn::make('user.full_name')->label(__('ConferenceDiscountEligibility::messages.linked_user'))->placeholder(__('ConferenceDiscountEligibility::messages.pending')),
            Tables\Columns\TextColumn::make('percentage_basis_points')->label(__('ConferenceDiscountEligibility::messages.discount'))->formatStateUsing(fn ($state) => Percentage::format((int) $state) . '%')->sortable(),
            Tables\Columns\TextColumn::make('reason')->label(__('ConferenceDiscountEligibility::messages.reason'))->limit(40),
            Tables\Columns\IconColumn::make('active')->label(__('ConferenceDiscountEligibility::messages.active'))->boolean(),
            Tables\Columns\TextColumn::make('valid_until')->label(__('ConferenceDiscountEligibility::messages.valid_until'))->dateTime()->placeholder('—'),
        ])->filters([Tables\Filters\TernaryFilter::make('active')->label(__('ConferenceDiscountEligibility::messages.active'))])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('recalculate')->label(__('ConferenceDiscountEligibility::messages.recalculate_unpaid'))->icon('heroicon-o-arrow-path')->requiresConfirmation()
                    ->visible(fn (ConferenceDiscountEntitlement $record) => $record->user_id !== null)
                    ->form([Forms\Components\Toggle::make('notify')->label(__('ConferenceDiscountEligibility::messages.notify_user'))])
                    ->action(function (ConferenceDiscountEntitlement $record, array $data): void {
                        $stats = app(RecalculationCoordinator::class)->run('entitlement', (int) $record->getKey(), (bool) ($data['notify'] ?? false));
                        RecalculationFeedback::send($stats);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $id = app()->getCurrentScheduledConference()?->getKey();
        return parent::getEloquentQuery()->where('eligibility_type', EligibilityType::Email->value)->where('scheduled_conference_id', $id ?? -1);
    }

    public static function canViewAny(): bool { return app(Authorization::class)->canManage(); }
    public static function canCreate(): bool { return app(Authorization::class)->canManage(); }
    public static function canEdit($record): bool { return app(Authorization::class)->canManage() && (int) $record->scheduled_conference_id === (int) app()->getCurrentScheduledConference()?->getKey(); }
    public static function canDelete($record): bool { return static::canEdit($record); }
    public static function canDeleteAny(): bool { return false; }
    public static function getPages(): array { return ['index' => Pages\ListEmailEntitlements::route('/'), 'create' => Pages\CreateEmailEntitlement::route('/create'), 'edit' => Pages\EditEmailEntitlement::route('/{record}/edit')]; }
}
