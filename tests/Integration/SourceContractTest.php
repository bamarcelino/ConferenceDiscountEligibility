<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class SourceContractTest extends TestCase
{
    public function testPluginDoesNotImplementPayPalOrMarkPaymentsPaid(): void
    {
        $root = dirname(__DIR__, 2);
        $source = file_get_contents($root . '/src/Managers/DiscountAwarePaymentManager.php');
        self::assertIsString($source);
        self::assertStringNotContainsString('fulfillQueued', $source);
        self::assertStringNotContainsString('paid_at', $source);
    }

    public function testPanelPackageManifestAndEntryPoint(): void
    {
        $root = dirname(__DIR__, 2);
        self::assertFileExists($root . '/index.php');
        self::assertFileExists($root . '/index.yaml');
        self::assertStringContainsString(
            'folder: ConferenceDiscountEligibility',
            (string) file_get_contents($root . '/index.yaml'),
        );
    }

    public function testAuthorFallbackUsesSubmissionEvidenceInTheCurrentConference(): void
    {
        $root = dirname(__DIR__, 2);
        $source = (string) file_get_contents($root . '/src/Services/AuthorIdentityVerifier.php');

        self::assertStringContainsString("where('scheduled_conference_id'", $source);
        self::assertStringContainsString("where('user_id'", $source);
        self::assertStringContainsString("whereHas('participants'", $source);
        self::assertStringContainsString("whereHas('role'", $source);
        self::assertStringContainsString('UserRole::Author->value', $source);
        self::assertStringContainsString("whereHas('authors'", $source);
        self::assertStringContainsString('LOWER(TRIM(email))', $source);
        self::assertStringContainsString('submission_author_email', $source);
    }

    public function testSelfAssignableAuthorRoleAloneIsNotAccepted(): void
    {
        $root = dirname(__DIR__, 2);
        $source = (string) file_get_contents($root . '/src/Services/AuthorIdentityVerifier.php');

        self::assertStringNotContainsString('hasRole(', $source);
        self::assertStringNotContainsString('roles()', $source);
        self::assertStringNotContainsString('Author::query', $source);
    }

    public function testDomainFallbackIsExplicitAndDefaultsToVerifiedEmail(): void
    {
        $root = dirname(__DIR__, 2);
        $schema = (string) file_get_contents($root . '/src/Database/SchemaDefinition.php');
        $resource = (string) file_get_contents(
            $root . '/src/Panel/ScheduledConference/Resources/InstitutionalDomainResource.php',
        );

        self::assertStringContainsString("default('verified_email_only')", $schema);
        self::assertStringContainsString('VerifiedEmailOrConfirmedAuthor', $resource);
        self::assertStringContainsString('VerifiedEmailOnly', $resource);
    }

    public function testCouponEntryUsesOfficialPaymentDetailHook(): void
    {
        $root = dirname(__DIR__, 2);
        $plugin = (string) file_get_contents($root . '/src/ConferenceDiscountEligibilityPlugin.php');
        $view = (string) file_get_contents($root . '/resources/views/infolists/coupon-redemption.blade.php');

        self::assertStringContainsString("PaymentManager::getPaymentMethodInfolist", $plugin);
        self::assertStringContainsString('ViewEntry::make', $plugin);
        self::assertStringContainsString('conference-discount-coupon-redemption', $view);
    }

    public function testCouponCodesAreHashedAndNeverPersistedAsPlaintext(): void
    {
        $root = dirname(__DIR__, 2);
        $schema = (string) file_get_contents($root . '/src/Database/SchemaDefinition.php');
        $model = (string) file_get_contents($root . '/src/Models/ConferenceDiscountCoupon.php');
        $support = (string) file_get_contents($root . '/src/Support/CouponCode.php');

        self::assertStringContainsString("char('code_hash', 64)", $schema);
        self::assertStringContainsString("string('code_hint'", $schema);
        self::assertStringNotContainsString("string('coupon_code'", $schema);
        self::assertStringContainsString("'code_hash'", $model);
        self::assertStringContainsString("hash_hmac('sha256'", $support);
    }

    public function testCouponCompletionObservesNativePaidStateWithoutReplacingPaypal(): void
    {
        $root = dirname(__DIR__, 2);
        $observer = (string) file_get_contents($root . '/src/Observers/PaymentObserver.php');
        $manager = (string) file_get_contents($root . '/src/Managers/DiscountAwarePaymentManager.php');

        self::assertStringContainsString("wasChanged('paid_at')", $observer);
        self::assertStringContainsString('consumeForPayment', $observer);
        self::assertStringNotContainsString('fulfillQueued', $manager);
        self::assertStringNotContainsString('Paypal', $manager);
    }

    public function testCouponChangesAreBlockedForPaidOrInitiatedPayments(): void
    {
        $root = dirname(__DIR__, 2);
        $livewire = (string) file_get_contents($root . '/src/Livewire/CouponRedemption.php');
        $service = (string) file_get_contents($root . '/src/Services/CouponRedemptionService.php');

        self::assertStringContainsString('PaymentSafety::canRecalculate', $livewire);
        self::assertStringContainsString('PaymentSafety::canRecalculate', $service);
        self::assertStringContainsString('lockForUpdate', $service);
    }

    public function testOneHundredPercentDiscountUsesNativeZeroValueFulfillmentWithoutPaypal(): void
    {
        $root = dirname(__DIR__, 2);
        $settlement = (string) file_get_contents(
            $root . '/src/Services/FullDiscountSettlementService.php',
        );

        self::assertStringContainsString('PaymentManager::get()->fulfillQueued', $settlement);
        self::assertStringContainsString('FullDiscountPolicy::PAYMENT_METHOD', $settlement);
        self::assertStringContainsString("'gateway_required' => false", $settlement);
        self::assertStringNotContainsString('Omnipay', $settlement);
        self::assertStringNotContainsString('paypal_payment_id', $settlement);
    }

    public function testPaymentRequiredNotificationsAreSuppressedAfterFullDiscountCompletion(): void
    {
        $root = dirname(__DIR__, 2);
        $plugin = (string) file_get_contents($root . '/src/ConferenceDiscountEligibilityPlugin.php');
        $listener = (string) file_get_contents(
            $root . '/src/Listeners/SuppressPaymentRequiredForFullDiscount.php',
        );

        self::assertStringContainsString('NotificationSending::class', $plugin);
        self::assertStringContainsString('SuppressPaymentRequiredForFullDiscount::class', $plugin);
        self::assertStringContainsString('ParticipantPayment', $listener);
        self::assertStringContainsString('SubmissionPayment', $listener);
        self::assertStringContainsString('return false', $listener);
    }

    public function testZeroTotalCompletionRunsAfterCouponReservationAndSnapshot(): void
    {
        $root = dirname(__DIR__, 2);
        $coupon = (string) file_get_contents($root . '/src/Services/CouponRedemptionService.php');
        $manager = (string) file_get_contents($root . '/src/Managers/DiscountAwarePaymentManager.php');

        self::assertLessThan(
            strpos($coupon, 'settleIfZero'),
            strpos($coupon, 'ConferenceDiscountCouponRedemption::query()->updateOrCreate'),
        );
        self::assertLessThan(
            strpos($manager, 'settleIfZero'),
            strpos($manager, '$this->snapshots->record'),
        );
    }

}
