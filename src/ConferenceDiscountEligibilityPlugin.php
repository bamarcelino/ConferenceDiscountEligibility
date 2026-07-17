<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility;

use App\Classes\Plugin;
use App\Facades\Hook;
use App\Managers\PaymentManager;
use App\Models\Meta;
use App\Models\Payment;
use App\Models\User;
use App\Panel\ScheduledConference\Pages\ParticipantRegistration;
use ConferenceDiscountEligibility\Managers\DiscountAwarePaymentManager;
use ConferenceDiscountEligibility\Models\ConferenceDiscountDomain;
use ConferenceDiscountEligibility\Models\ConferenceDiscountEntitlement;
use ConferenceDiscountEligibility\Observers\DomainObserver;
use ConferenceDiscountEligibility\Observers\EntitlementObserver;
use ConferenceDiscountEligibility\Observers\MetaObserver;
use ConferenceDiscountEligibility\Observers\UserObserver;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Pages\DiscountCsvImport;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Pages\DiscountSettings;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\AuditLogResource;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\DiscountPaymentReportResource;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\EmailEntitlementResource;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\IndividualEntitlementResource;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\InstitutionalDomainResource;
use ConferenceDiscountEligibility\Services\CompatibilityGuard;
use ConferenceDiscountEligibility\Services\PaymentDetailPresenter;
use ConferenceDiscountEligibility\Services\SchemaInstaller;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

final class ConferenceDiscountEligibilityPlugin extends Plugin
{
    private static bool $bootedOnce = false;

    public function boot()
    {
        if (self::$bootedOnce || ! app()->getCurrentScheduledConference()) {
            return;
        }

        app(CompatibilityGuard::class)->assertCompatible();
        app(SchemaInstaller::class)->install();

        app()->forgetInstance(PaymentManager::class);
        app()->bind(PaymentManager::class, DiscountAwarePaymentManager::class);

        ConferenceDiscountEntitlement::observe(EntitlementObserver::class);
        ConferenceDiscountDomain::observe(DomainObserver::class);
        User::observe(UserObserver::class);
        Meta::observe(MetaObserver::class);

        $this->registerPaymentDetailHook();
        $this->registerRegistrationPreviewHook();

        self::$bootedOnce = true;
    }

    public function onPanel(Panel $panel): void
    {
        if ($panel->getId() !== 'scheduledConference') {
            return;
        }

        $panel
            ->resources([
                IndividualEntitlementResource::class,
                EmailEntitlementResource::class,
                InstitutionalDomainResource::class,
                AuditLogResource::class,
                DiscountPaymentReportResource::class,
            ])
            ->pages([
                DiscountCsvImport::class,
                DiscountSettings::class,
            ]);
    }

    private function registerPaymentDetailHook(): void
    {
        Hook::add('PaymentManager::getPaymentMethodInfolist', static function (string $hookName, array &$schemas): bool {
            $presenter = app(PaymentDetailPresenter::class);

            $schemas[] = Section::make(__('ConferenceDiscountEligibility::messages.discount_details'))
                ->visible(static fn (Payment $record): bool => $presenter->visible($record))
                ->schema([
                    TextEntry::make('cde_standard_fee')
                        ->label(__('ConferenceDiscountEligibility::messages.standard_fee'))
                        ->getStateUsing(static fn (Payment $record): string => $presenter->money($record, 'original_base_amount_minor')),
                    TextEntry::make('cde_discount_percentage')
                        ->label(__('ConferenceDiscountEligibility::messages.discount'))
                        ->getStateUsing(static fn (Payment $record): string => $presenter->percentage($record)),
                    TextEntry::make('cde_discount_amount')
                        ->label(__('ConferenceDiscountEligibility::messages.discount_amount'))
                        ->getStateUsing(static fn (Payment $record): string => $presenter->money($record, 'discount_amount_minor', true)),
                    TextEntry::make('cde_add_ons')
                        ->label(__('ConferenceDiscountEligibility::messages.add_ons'))
                        ->getStateUsing(static fn (Payment $record): string => $presenter->money($record, 'add_on_amount_minor')),
                    TextEntry::make('cde_final_total')
                        ->label(__('ConferenceDiscountEligibility::messages.final_total'))
                        ->getStateUsing(static fn (Payment $record): string => $presenter->money($record, 'final_total_minor')),
                    TextEntry::make('cde_eligibility_reason')
                        ->label(__('ConferenceDiscountEligibility::messages.reason'))
                        ->getStateUsing(static fn (Payment $record): string => (string) ($presenter->snapshot($record)?->eligibility_reason ?? '—')),
                    TextEntry::make('cde_eligibility_type')
                        ->label(__('ConferenceDiscountEligibility::messages.origin'))
                        ->badge()
                        ->getStateUsing(static fn (Payment $record): string => (string) ($presenter->snapshot($record)?->eligibility_type ?? '—')),
                    TextEntry::make('cde_identity_evidence')
                        ->label(__('ConferenceDiscountEligibility::messages.identity_evidence'))
                        ->visible(static fn (Payment $record): bool => $presenter->snapshot($record)?->eligibility_type === 'domain')
                        ->getStateUsing(static fn (Payment $record): string => $presenter->identityEvidence($record)),
                    TextEntry::make('cde_snapshot_at')
                        ->label(__('ConferenceDiscountEligibility::messages.snapshot_at'))
                        ->dateTime()
                        ->getStateUsing(static fn (Payment $record) => $presenter->snapshot($record)?->eligibility_snapshot_at),
                ])
                ->columns(2);

            return false;
        });
    }

    private function registerRegistrationPreviewHook(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_START,
            static fn () => view('ConferenceDiscountEligibility::registration-preview'),
            [ParticipantRegistration::class],
        );
    }
}
