<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources;

use Closure;
use App\Models\PaymentFee;
use ConferenceDiscountEligibility\Models\ConferenceDiscountCoupon;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\CouponCampaignResource\Pages;
use ConferenceDiscountEligibility\Services\Authorization;
use ConferenceDiscountEligibility\Support\CouponCode;
use ConferenceDiscountEligibility\Support\CouponPaymentTypes;
use ConferenceDiscountEligibility\Support\Percentage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class CouponCampaignResource extends Resource
{
    protected static ?string $model = ConferenceDiscountCoupon::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?int $navigationSort = 35;

    public static function getNavigationGroup(): ?string { return __('ConferenceDiscountEligibility::messages.navigation_group'); }
    public static function getNavigationLabel(): string { return __('ConferenceDiscountEligibility::messages.coupon_campaigns'); }
    public static function getModelLabel(): string { return __('ConferenceDiscountEligibility::messages.coupon_campaign'); }
    public static function getPluralModelLabel(): string { return __('ConferenceDiscountEligibility::messages.coupon_campaigns'); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('ConferenceDiscountEligibility::messages.coupon_campaign_name'))
                ->required()
                ->maxLength(255),
            Forms\Components\Radio::make('code_mode')
                ->label(__('ConferenceDiscountEligibility::messages.coupon_code_mode'))
                ->options([
                    'generate' => __('ConferenceDiscountEligibility::messages.coupon_generate_automatically'),
                    'manual' => __('ConferenceDiscountEligibility::messages.coupon_enter_manually'),
                ])
                ->default('generate')
                ->live()
                ->visible(static fn (string $operation): bool => $operation === 'create'),
            Forms\Components\TextInput::make('coupon_code')
                ->label(__('ConferenceDiscountEligibility::messages.coupon_code'))
                ->helperText(__('ConferenceDiscountEligibility::messages.coupon_code_help'))
                ->required(static fn (Forms\Get $get): bool => $get('code_mode') === 'manual')
                ->visible(static fn (Forms\Get $get, string $operation): bool => $operation === 'create' && $get('code_mode') === 'manual')
                ->minLength(4)
                ->maxLength(64)
                ->regex('/^[A-Za-z0-9_-]+$/D')
                ->rule(static function (): Closure {
                    return static function (string $attribute, mixed $value, Closure $fail): void {
                        try {
                            $hash = CouponCode::hash((string) $value);
                        } catch (\InvalidArgumentException) {
                            $fail(__('ConferenceDiscountEligibility::messages.coupon_invalid_format'));

                            return;
                        }

                        $conferenceId = (int) app()->getCurrentScheduledConference()?->getKey();
                        if ($conferenceId > 0 && ConferenceDiscountCoupon::query()
                            ->where('scheduled_conference_id', $conferenceId)
                            ->where('code_hash', $hash)
                            ->exists()) {
                            $fail(__('ConferenceDiscountEligibility::messages.coupon_code_already_exists'));
                        }
                    };
                }),
            Forms\Components\TextInput::make('percentage')
                ->label(__('ConferenceDiscountEligibility::messages.percentage'))
                ->numeric()->minValue(0.01)->maxValue(100)->step(0.01)
                ->datalist(['40', '30'])
                ->suffix('%')->required(),
            Forms\Components\TextInput::make('reason')
                ->label(__('ConferenceDiscountEligibility::messages.reason'))
                ->datalist(IndividualEntitlementResource::reasonPresets())
                ->required()->maxLength(255),
            Forms\Components\CheckboxList::make('eligible_payment_types')
                ->label(__('ConferenceDiscountEligibility::messages.coupon_eligible_payment_types'))
                ->options([
                    CouponPaymentTypes::PARTICIPANT => __('ConferenceDiscountEligibility::messages.payment_type_participant'),
                    CouponPaymentTypes::SUBMISSION => __('ConferenceDiscountEligibility::messages.payment_type_submission'),
                ])
                ->default(CouponPaymentTypes::all())
                ->columns(2)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Select::make('eligible_payment_fee_ids')
                ->label(__('ConferenceDiscountEligibility::messages.coupon_eligible_payment_fees'))
                ->helperText(__('ConferenceDiscountEligibility::messages.coupon_eligible_payment_fees_help'))
                ->multiple()->searchable()->preload()
                ->options(static fn (): array => PaymentFee::query()
                    ->where('scheduled_conference_id', app()->getCurrentScheduledConference()?->getKey() ?? -1)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->columnSpanFull(),
            Forms\Components\DateTimePicker::make('valid_from')->label(__('ConferenceDiscountEligibility::messages.valid_from'))->native(false),
            Forms\Components\DateTimePicker::make('valid_until')->label(__('ConferenceDiscountEligibility::messages.valid_until'))->native(false)->afterOrEqual('valid_from'),
            Forms\Components\Toggle::make('active')->label(__('ConferenceDiscountEligibility::messages.active'))->default(true),
            Forms\Components\TextInput::make('maximum_uses')->label(__('ConferenceDiscountEligibility::messages.maximum_uses'))->numeric()->integer()->minValue(1),
            Forms\Components\TextInput::make('per_user_limit')->label(__('ConferenceDiscountEligibility::messages.coupon_per_user_limit'))->numeric()->integer()->minValue(1)->default(1)->required(),
            Forms\Components\Textarea::make('notes')->label(__('ConferenceDiscountEligibility::messages.notes'))->maxLength(5000)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label(__('ConferenceDiscountEligibility::messages.coupon_campaign_name'))->searchable()->sortable(),
            Tables\Columns\TextColumn::make('code_hint')->label(__('ConferenceDiscountEligibility::messages.coupon_code'))->badge(),
            Tables\Columns\TextColumn::make('percentage_basis_points')->label(__('ConferenceDiscountEligibility::messages.discount'))->formatStateUsing(fn ($state) => Percentage::format((int) $state) . '%')->sortable(),
            Tables\Columns\TextColumn::make('eligible_payment_types')->label(__('ConferenceDiscountEligibility::messages.coupon_eligible_payment_types'))->formatStateUsing(fn ($state) => collect($state)->map(fn ($type) => $type === CouponPaymentTypes::PARTICIPANT ? __('ConferenceDiscountEligibility::messages.payment_type_participant') : __('ConferenceDiscountEligibility::messages.payment_type_submission'))->implode(', '))->wrap(),
            Tables\Columns\TextColumn::make('uses_count')->label(__('ConferenceDiscountEligibility::messages.uses'))->formatStateUsing(fn ($state, ConferenceDiscountCoupon $record) => $record->maximum_uses ? "{$state} / {$record->maximum_uses}" : (string) $state)->sortable(),
            Tables\Columns\IconColumn::make('active')->label(__('ConferenceDiscountEligibility::messages.active'))->boolean(),
            Tables\Columns\TextColumn::make('valid_until')->label(__('ConferenceDiscountEligibility::messages.valid_until'))->dateTime()->placeholder('—')->sortable(),
        ])->filters([
            Tables\Filters\TernaryFilter::make('active')->label(__('ConferenceDiscountEligibility::messages.active')),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('regenerate_code')
                ->label(__('ConferenceDiscountEligibility::messages.coupon_regenerate_code'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (ConferenceDiscountCoupon $record): bool => (int) $record->uses_count === 0)
                ->requiresConfirmation()
                ->action(function (ConferenceDiscountCoupon $record): void {
                    $code = CouponCode::generate('CDE');
                    $record->update([
                        'code_hash' => CouponCode::hash($code),
                        'code_hint' => CouponCode::hint($code),
                    ]);
                    Notification::make()
                        ->success()
                        ->persistent()
                        ->title(__('ConferenceDiscountEligibility::messages.coupon_code_generated'))
                        ->body(__('ConferenceDiscountEligibility::messages.coupon_copy_now', ['code' => $code]))
                        ->send();
                }),
            Tables\Actions\DeleteAction::make()
                ->visible(fn (ConferenceDiscountCoupon $record): bool => (int) $record->uses_count === 0),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('scheduled_conference_id', app()->getCurrentScheduledConference()?->getKey() ?? -1);
    }

    public static function canViewAny(): bool { return app(Authorization::class)->canManage(); }
    public static function canCreate(): bool { return app(Authorization::class)->canManage(); }
    public static function canEdit($record): bool { return app(Authorization::class)->canManage() && (int) $record->scheduled_conference_id === (int) app()->getCurrentScheduledConference()?->getKey(); }
    public static function canDelete($record): bool { return static::canEdit($record) && (int) $record->uses_count === 0; }
    public static function canDeleteAny(): bool { return false; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCouponCampaigns::route('/'),
            'create' => Pages\CreateCouponCampaign::route('/create'),
            'edit' => Pages\EditCouponCampaign::route('/{record}/edit'),
        ];
    }
}
