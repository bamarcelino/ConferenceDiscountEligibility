<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Pages;

use ConferenceDiscountEligibility\Enums\DiscountScope;
use ConferenceDiscountEligibility\Services\AuditLogger;
use ConferenceDiscountEligibility\Services\Authorization;
use ConferenceDiscountEligibility\Services\SettingsRepository;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

final class DiscountSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?int $navigationSort = 50;
    protected static string $view = 'ConferenceDiscountEligibility::pages.settings';

    /** @var array<string,mixed>|null */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string { return __('ConferenceDiscountEligibility::messages.navigation_group'); }
    public static function getNavigationLabel(): string { return __('ConferenceDiscountEligibility::messages.settings'); }
    public function getTitle(): string { return __('ConferenceDiscountEligibility::messages.settings'); }
    public static function canAccess(): bool { return app(Authorization::class)->canManage(); }

    public function mount(SettingsRepository $settings): void
    {
        app(Authorization::class)->authorizeManage();
        $record = $settings->forConference((int) app()->getCurrentScheduledConference()->getKey());
        $this->form->fill([
            'discount_scope' => $record->discount_scope,
            'eligible_add_on_keys' => $record->eligible_add_on_keys ?? [],
            'recalculate_unpaid_default' => (bool) $record->recalculate_unpaid_default,
            'notify_on_recalculation' => (bool) $record->notify_on_recalculation,
            'coupon_redemption_enabled' => (bool) $record->coupon_redemption_enabled,
            'csv_max_megabytes' => max(1, (int) ceil(((int) $record->csv_max_bytes) / 1048576)),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Radio::make('discount_scope')
                ->label(__('ConferenceDiscountEligibility::messages.discount_scope'))
                ->options([
                    DiscountScope::BaseRegistrationFeeOnly->value => __('ConferenceDiscountEligibility::messages.scope_base_only'),
                    DiscountScope::BaseFeeAndEligibleAddOns->value => __('ConferenceDiscountEligibility::messages.scope_base_and_add_ons'),
                ])->descriptions([
                    DiscountScope::BaseRegistrationFeeOnly->value => __('ConferenceDiscountEligibility::messages.scope_base_only_help'),
                    DiscountScope::BaseFeeAndEligibleAddOns->value => __('ConferenceDiscountEligibility::messages.scope_add_ons_help'),
                ])->required(),
            Forms\Components\TagsInput::make('eligible_add_on_keys')
                ->label(__('ConferenceDiscountEligibility::messages.eligible_add_on_keys'))
                ->helperText(__('ConferenceDiscountEligibility::messages.eligible_add_on_keys_help'))
                ->visible(fn (Forms\Get $get) => $get('discount_scope') === DiscountScope::BaseFeeAndEligibleAddOns->value),
            Forms\Components\Toggle::make('recalculate_unpaid_default')->label(__('ConferenceDiscountEligibility::messages.recalculate_default'))->helperText(__('ConferenceDiscountEligibility::messages.recalculate_warning')),
            Forms\Components\Toggle::make('notify_on_recalculation')->label(__('ConferenceDiscountEligibility::messages.notify_default')),
            Forms\Components\Toggle::make('coupon_redemption_enabled')->label(__('ConferenceDiscountEligibility::messages.coupon_redemption_enabled'))->helperText(__('ConferenceDiscountEligibility::messages.coupon_redemption_enabled_help'))->default(true),
            Forms\Components\TextInput::make('csv_max_megabytes')->label(__('ConferenceDiscountEligibility::messages.csv_max_size'))->numeric()->integer()->minValue(1)->maxValue(20)->suffix('MB')->required(),
        ])->statePath('data')->columns(1);
    }

    public function save(SettingsRepository $settings, AuditLogger $auditLogger): void
    {
        app(Authorization::class)->authorizeManage();
        $data = $this->form->getState();
        $record = $settings->forConference((int) app()->getCurrentScheduledConference()->getKey());
        $old = $record->toArray();
        $record->update([
            'discount_scope' => $data['discount_scope'],
            'eligible_add_on_keys' => array_values(array_unique(array_filter(array_map('strval', $data['eligible_add_on_keys'] ?? [])))),
            'recalculate_unpaid_default' => (bool) ($data['recalculate_unpaid_default'] ?? false),
            'notify_on_recalculation' => (bool) ($data['notify_on_recalculation'] ?? false),
            'coupon_redemption_enabled' => (bool) ($data['coupon_redemption_enabled'] ?? true),
            'csv_max_bytes' => (int) $data['csv_max_megabytes'] * 1048576,
        ]);
        $auditLogger->log('settings_updated', (int) $record->scheduled_conference_id, $record, oldValues: $old, newValues: $record->fresh()->toArray());
        Notification::make()->success()->title(__('ConferenceDiscountEligibility::messages.saved'))->send();
    }
}
