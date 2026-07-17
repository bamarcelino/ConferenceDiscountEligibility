<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources;

use ConferenceDiscountEligibility\Enums\DomainIdentityPolicy;
use ConferenceDiscountEligibility\Models\ConferenceDiscountDomain;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\InstitutionalDomainResource\Pages;
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

final class InstitutionalDomainResource extends Resource
{
    protected static ?string $model = ConferenceDiscountDomain::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): ?string
    {
        return __('ConferenceDiscountEligibility::messages.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('ConferenceDiscountEligibility::messages.institutional_domains');
    }

    public static function getModelLabel(): string
    {
        return __('ConferenceDiscountEligibility::messages.institutional_domain');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ConferenceDiscountEligibility::messages.institutional_domains');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('original_domain')
                ->label(__('ConferenceDiscountEligibility::messages.domain'))
                ->placeholder('universidade.edu')
                ->required()
                ->maxLength(253),
            Forms\Components\TextInput::make('percentage')
                ->label(__('ConferenceDiscountEligibility::messages.percentage'))
                ->numeric()
                ->minValue(0.01)
                ->maxValue(100)
                ->step(0.01)
                ->datalist(['40', '30'])
                ->suffix('%')
                ->required(),
            Forms\Components\TextInput::make('reason')
                ->label(__('ConferenceDiscountEligibility::messages.reason'))
                ->datalist(IndividualEntitlementResource::reasonPresets())
                ->required()
                ->maxLength(255),
            Forms\Components\Toggle::make('include_subdomains')
                ->label(__('ConferenceDiscountEligibility::messages.include_subdomains'))
                ->default(false),
            Forms\Components\Radio::make('identity_policy')
                ->label(__('ConferenceDiscountEligibility::messages.domain_identity_policy'))
                ->options([
                    DomainIdentityPolicy::VerifiedEmailOnly->value => __('ConferenceDiscountEligibility::messages.domain_policy_verified_only'),
                    DomainIdentityPolicy::VerifiedEmailOrConfirmedAuthor->value => __('ConferenceDiscountEligibility::messages.domain_policy_confirmed_author'),
                ])
                ->descriptions([
                    DomainIdentityPolicy::VerifiedEmailOnly->value => __('ConferenceDiscountEligibility::messages.domain_policy_verified_only_help'),
                    DomainIdentityPolicy::VerifiedEmailOrConfirmedAuthor->value => __('ConferenceDiscountEligibility::messages.domain_policy_confirmed_author_help'),
                ])
                ->default(DomainIdentityPolicy::VerifiedEmailOnly->value)
                ->required()
                ->columnSpanFull(),
            Forms\Components\DateTimePicker::make('valid_from')
                ->label(__('ConferenceDiscountEligibility::messages.valid_from'))
                ->native(false),
            Forms\Components\DateTimePicker::make('valid_until')
                ->label(__('ConferenceDiscountEligibility::messages.valid_until'))
                ->native(false)
                ->afterOrEqual('valid_from'),
            Forms\Components\Toggle::make('active')
                ->label(__('ConferenceDiscountEligibility::messages.active'))
                ->default(true),
            Forms\Components\TextInput::make('maximum_uses')
                ->label(__('ConferenceDiscountEligibility::messages.maximum_uses'))
                ->numeric()
                ->integer()
                ->minValue(1),
            Forms\Components\Textarea::make('notes')
                ->label(__('ConferenceDiscountEligibility::messages.notes'))
                ->maxLength(5000)
                ->columnSpanFull(),
            Forms\Components\Toggle::make('recalculate_unpaid_payments')
                ->label(__('ConferenceDiscountEligibility::messages.recalculate_eligible_unpaid_payments'))
                ->helperText(__('ConferenceDiscountEligibility::messages.recalculate_warning'))
                ->default(fn () => app(SettingsRepository::class)
                    ->forConference((int) app()->getCurrentScheduledConference()?->getKey())
                    ->recalculate_unpaid_default),
            Forms\Components\Toggle::make('notify_on_recalculation')
                ->label(__('ConferenceDiscountEligibility::messages.notify_user'))
                ->default(fn () => app(SettingsRepository::class)
                    ->forConference((int) app()->getCurrentScheduledConference()?->getKey())
                    ->notify_on_recalculation),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('normalized_domain')
                    ->label(__('ConferenceDiscountEligibility::messages.domain'))
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('percentage_basis_points')
                    ->label(__('ConferenceDiscountEligibility::messages.discount'))
                    ->formatStateUsing(fn ($state) => Percentage::format((int) $state) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label(__('ConferenceDiscountEligibility::messages.reason'))
                    ->limit(40),
                Tables\Columns\IconColumn::make('include_subdomains')
                    ->label(__('ConferenceDiscountEligibility::messages.include_subdomains'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('identity_policy')
                    ->label(__('ConferenceDiscountEligibility::messages.domain_identity_policy_short'))
                    ->badge()
                    ->formatStateUsing(static fn (?string $state): string => match (DomainIdentityPolicy::tryFrom((string) $state)) {
                        DomainIdentityPolicy::VerifiedEmailOrConfirmedAuthor => __('ConferenceDiscountEligibility::messages.domain_policy_confirmed_author_short'),
                        default => __('ConferenceDiscountEligibility::messages.domain_policy_verified_only_short'),
                    }),
                Tables\Columns\IconColumn::make('active')
                    ->label(__('ConferenceDiscountEligibility::messages.active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('uses_count')
                    ->label(__('ConferenceDiscountEligibility::messages.uses'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('ConferenceDiscountEligibility::messages.active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('recalculate')
                    ->label(__('ConferenceDiscountEligibility::messages.recalculate_unpaid'))
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Toggle::make('notify')
                            ->label(__('ConferenceDiscountEligibility::messages.notify_user')),
                    ])
                    ->action(function (ConferenceDiscountDomain $record, array $data): void {
                        $stats = app(RecalculationCoordinator::class)->run(
                            'domain',
                            (int) $record->getKey(),
                            (bool) ($data['notify'] ?? false),
                        );
                        RecalculationFeedback::send($stats);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $id = app()->getCurrentScheduledConference()?->getKey();

        return parent::getEloquentQuery()
            ->where('scheduled_conference_id', $id ?? -1);
    }

    public static function canViewAny(): bool
    {
        return app(Authorization::class)->canManage();
    }

    public static function canCreate(): bool
    {
        return app(Authorization::class)->canManage();
    }

    public static function canEdit($record): bool
    {
        return app(Authorization::class)->canManage()
            && (int) $record->scheduled_conference_id
                === (int) app()->getCurrentScheduledConference()?->getKey();
    }

    public static function canDelete($record): bool
    {
        return static::canEdit($record);
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstitutionalDomains::route('/'),
            'create' => Pages\CreateInstitutionalDomain::route('/create'),
            'edit' => Pages\EditInstitutionalDomain::route('/{record}/edit'),
        ];
    }
}
