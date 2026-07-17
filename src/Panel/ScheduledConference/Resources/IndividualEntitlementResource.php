<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources;

use App\Models\User;
use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Jobs\RecalculateEligibleUnpaidPayments;
use ConferenceDiscountEligibility\Models\ConferenceDiscountEntitlement;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\IndividualEntitlementResource\Pages;
use ConferenceDiscountEligibility\Services\Authorization;
use ConferenceDiscountEligibility\Services\SettingsRepository;
use ConferenceDiscountEligibility\Support\Percentage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class IndividualEntitlementResource extends Resource
{
    protected static ?string $model = ConferenceDiscountEntitlement::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string { return __('ConferenceDiscountEligibility::messages.navigation_group'); }
    public static function getNavigationLabel(): string { return __('ConferenceDiscountEligibility::messages.individual_entitlements'); }
    public static function getModelLabel(): string { return __('ConferenceDiscountEligibility::messages.individual_entitlement'); }
    public static function getPluralModelLabel(): string { return __('ConferenceDiscountEligibility::messages.individual_entitlements'); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label(__('ConferenceDiscountEligibility::messages.user'))
                ->required()->searchable()
                ->getSearchResultsUsing(function (string $search): array {
                    return User::query()->where(function (Builder $query) use ($search): void {
                        $query->where('email', 'like', "%{$search}%")
                            ->orWhere('given_name', 'like', "%{$search}%")
                            ->orWhere('family_name', 'like', "%{$search}%");
                        if (ctype_digit($search)) { $query->orWhereKey((int) $search); }
                    })->limit(50)->get()->mapWithKeys(fn (User $user) => [$user->getKey() => "{$user->full_name} — {$user->email} (#{$user->getKey()})"])->all();
                })
                ->getOptionLabelUsing(fn ($value): ?string => ($user = User::find($value)) ? "{$user->full_name} — {$user->email} (#{$user->getKey()})" : null),
            Forms\Components\TextInput::make('percentage')->label(__('ConferenceDiscountEligibility::messages.percentage'))->numeric()->minValue(0.01)->maxValue(100)->step(0.01)->datalist(['40','30'])->suffix('%')->required(),
            Forms\Components\TextInput::make('reason')->label(__('ConferenceDiscountEligibility::messages.reason'))->datalist(static::reasonPresets())->required()->maxLength(255),
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
            Tables\Columns\TextColumn::make('user.full_name')->label(__('ConferenceDiscountEligibility::messages.user'))->searchable(['given_name','family_name'])->sortable(),
            Tables\Columns\TextColumn::make('user.email')->label(__('ConferenceDiscountEligibility::messages.email'))->searchable(),
            Tables\Columns\TextColumn::make('percentage_basis_points')->label(__('ConferenceDiscountEligibility::messages.discount'))->formatStateUsing(fn ($state) => Percentage::format((int) $state) . '%')->sortable(),
            Tables\Columns\TextColumn::make('reason')->label(__('ConferenceDiscountEligibility::messages.reason'))->searchable()->limit(40),
            Tables\Columns\IconColumn::make('active')->label(__('ConferenceDiscountEligibility::messages.active'))->boolean(),
            Tables\Columns\TextColumn::make('valid_until')->label(__('ConferenceDiscountEligibility::messages.valid_until'))->dateTime()->placeholder('—'),
            Tables\Columns\TextColumn::make('uses_count')->label(__('ConferenceDiscountEligibility::messages.uses'))->sortable(),
        ])->filters([
            Tables\Filters\TernaryFilter::make('active')->label(__('ConferenceDiscountEligibility::messages.active')),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('recalculate')
                ->label(__('ConferenceDiscountEligibility::messages.recalculate_unpaid'))
                ->icon('heroicon-o-arrow-path')->requiresConfirmation()
                ->form([Forms\Components\Toggle::make('notify')->label(__('ConferenceDiscountEligibility::messages.notify_user'))])
                ->action(function (ConferenceDiscountEntitlement $record, array $data): void {
                    RecalculateEligibleUnpaidPayments::dispatchSync('entitlement', (int) $record->getKey(), (bool) ($data['notify'] ?? false));
                    Notification::make()->success()->title(__('ConferenceDiscountEligibility::messages.recalculation_queued'))->send();
                }),
            Tables\Actions\DeleteAction::make(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $id = app()->getCurrentScheduledConference()?->getKey();
        return parent::getEloquentQuery()->where('eligibility_type', EligibilityType::User->value)->where('scheduled_conference_id', $id ?? -1);
    }

    public static function canViewAny(): bool { return app(Authorization::class)->canManage(); }
    public static function canCreate(): bool { return app(Authorization::class)->canManage(); }
    public static function canEdit($record): bool { return app(Authorization::class)->canManage() && (int) $record->scheduled_conference_id === (int) app()->getCurrentScheduledConference()?->getKey(); }
    public static function canDelete($record): bool { return static::canEdit($record); }
    public static function canDeleteAny(): bool { return false; }

    public static function getPages(): array
    {
        return ['index' => Pages\ListIndividualEntitlements::route('/'), 'create' => Pages\CreateIndividualEntitlement::route('/create'), 'edit' => Pages\EditIndividualEntitlement::route('/{record}/edit')];
    }

    public static function reasonPresets(): array
    {
        return ['CLAEC active member','Institutional partner affiliate','Research4Life Group A','Research4Life Group B','Individual approval','Other'];
    }
}
