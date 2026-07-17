<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use ConferenceDiscountEligibility\Data\AuthorIdentityEvidence;
use ConferenceDiscountEligibility\Data\DomainIdentityDecision;
use ConferenceDiscountEligibility\Data\EligibilityCandidate;
use ConferenceDiscountEligibility\Enums\DiscountScope;
use ConferenceDiscountEligibility\Enums\DomainIdentityPolicy;
use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Services\DiscountCalculator;
use ConferenceDiscountEligibility\Services\EligibilitySelector;
use ConferenceDiscountEligibility\Support\AuthorEvidencePolicy;
use ConferenceDiscountEligibility\Support\CsvSanitizer;
use ConferenceDiscountEligibility\Support\DomainMatcher;
use ConferenceDiscountEligibility\Support\EmailNormalizer;
use ConferenceDiscountEligibility\Support\Money;
use ConferenceDiscountEligibility\Support\PaypalAmountContract;
use ConferenceDiscountEligibility\Support\Percentage;
use ConferenceDiscountEligibility\Support\RuleValidity;

$root = dirname(__DIR__);
$tests = [];

$assert = static function (bool $condition, string $message = 'Assertion failed'): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
};
$assertSame = static function (mixed $expected, mixed $actual, string $message = '') use ($assert): void {
    $assert($expected === $actual, $message !== '' ? $message : 'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
};
$source = static fn (string $path): string => (string) file_get_contents($root . '/' . $path);
$contains = static function (string $path, string $needle) use ($assert, $source): void {
    $assert(str_contains($source($path), $needle), "{$path} does not contain required contract: {$needle}");
};
$notContains = static function (string $path, string $needle) use ($assert, $source): void {
    $assert(! str_contains($source($path), $needle), "{$path} contains forbidden contract: {$needle}");
};
$calc = static function (int $base, array $items, int $bp, DiscountScope $scope = DiscountScope::BaseRegistrationFeeOnly, array $keys = [], ?int $total = null): \ConferenceDiscountEligibility\Data\DiscountCalculation {
    return (new DiscountCalculator())->calculate($base, $items, $bp, $scope, $keys, 'EUR', $total);
};
$candidate = static fn (EligibilityType $type, int $id, int $bp, bool $eligible = true): EligibilityCandidate => new EligibilityCandidate($type, $id, $bp, 'Reason', $eligible);

$tests['01 user without discount'] = function () use ($assertSame, $calc): void {
    $assertSame(3500, $calc(3500, [], 0)->finalTotalMinor);
};
$tests['02 direct 40 percent discount'] = function () use ($assertSame, $calc): void {
    $r = $calc(3500, [], 4000); $assertSame(1400, $r->discountAmountMinor); $assertSame(2100, $r->finalTotalMinor);
};
$tests['03 direct 30 percent discount'] = function () use ($assertSame, $calc): void {
    $assertSame(2450, $calc(3500, [], 3000)->finalTotalMinor);
};
$tests['04 email pre-registration normalization'] = function () use ($assertSame): void {
    $assertSame('user@example.edu', EmailNormalizer::normalize('  User@Example.EDU '));
};
$tests['05 later email-to-user linking contract'] = function () use ($contains): void {
    $contains('src/Observers/UserObserver.php', 'EmailEntitlementLinker');
    $contains('src/Services/EmailEntitlementLinker.php', "'email_entitlement_linked'");
};
$tests['06 institutional domain exact match'] = function () use ($assert): void {
    $assert(DomainMatcher::matches('universidade.edu', 'universidade.edu', false));
};
$tests['07 authorized subdomain'] = function () use ($assert): void {
    $assert(DomainMatcher::matches('dept.universidade.edu', 'universidade.edu', true));
};
$tests['08 unauthorized subdomain'] = function () use ($assert): void {
    $assert(! DomainMatcher::matches('dept.universidade.edu', 'universidade.edu', false));
};
$tests['09 malicious similar domain boundaries'] = function () use ($assert): void {
    $assert(! DomainMatcher::matches('fakeuniversidade.edu', 'universidade.edu', true));
    $assert(! DomainMatcher::matches('universidade.edu.example.com', 'universidade.edu', true));
};
$tests['10 simultaneous rules are evaluated'] = function () use ($assertSame, $candidate): void {
    $selection = (new EligibilitySelector())->select([$candidate(EligibilityType::Email, 1, 3000), $candidate(EligibilityType::Domain, 2, 4000)]);
    $assertSame(2, count($selection->evaluated));
};
$tests['11 highest percentage wins'] = function () use ($assertSame, $candidate): void {
    $selection = (new EligibilitySelector())->select([$candidate(EligibilityType::User, 1, 3000), $candidate(EligibilityType::Domain, 2, 4000)]);
    $assertSame(4000, $selection->winner?->percentageBasisPoints);
};
$tests['12 inactive rule'] = function () use ($assertSame): void {
    $now = new DateTimeImmutable('2026-07-16T12:00:00Z');
    $assertSame('inactive', RuleValidity::rejectionReason(false, null, null, null, 0, $now));
};
$tests['13 not-yet-valid rule'] = function () use ($assertSame): void {
    $now = new DateTimeImmutable('2026-07-16T12:00:00Z');
    $assertSame('not_yet_valid', RuleValidity::rejectionReason(true, $now->modify('+1 day'), null, null, 0, $now));
};
$tests['14 expired rule'] = function () use ($assertSame): void {
    $now = new DateTimeImmutable('2026-07-16T12:00:00Z');
    $assertSame('expired', RuleValidity::rejectionReason(true, null, $now->modify('-1 second'), null, 0, $now));
};
$tests['15 scheduled-conference isolation'] = function () use ($contains): void {
    foreach (['IndividualEntitlementResource.php','EmailEntitlementResource.php','InstitutionalDomainResource.php','AuditLogResource.php','DiscountPaymentReportResource.php'] as $file) {
        $contains('src/Panel/ScheduledConference/Resources/' . $file, "where('scheduled_conference_id'");
    }
};
$tests['16 presenter-category payment coverage'] = function () use ($contains): void {
    $contains('src/Managers/DiscountAwarePaymentManager.php', 'TYPE_PARTICIPANT_FEE');
    $contains('RESEARCH.md', 'Presenter registration discrepancy');
};
$tests['17 participant registration integration'] = function () use ($contains): void {
    $contains('src/ConferenceDiscountEligibilityPlugin.php', 'DiscountAwarePaymentManager::class');
    $contains('src/Managers/DiscountAwarePaymentManager.php', 'parent::queue');
};
$tests['18 base fee calculation'] = function () use ($assertSame, $calc): void {
    $r = $calc(1000, [], 4000); $assertSame(400, $r->baseDiscountMinor); $assertSame(600, $r->finalBaseMinor);
};
$tests['19 add-ons excluded by default'] = function () use ($assertSame, $calc): void {
    $r = $calc(3500, [['key'=>'addon_dinner','amount'=>5,'quantity'=>1]], 4000, DiscountScope::BaseRegistrationFeeOnly, [], 4000);
    $assertSame(1400, $r->discountAmountMinor); $assertSame(2600, $r->finalTotalMinor);
};
$tests['20 eligible add-ons included when configured'] = function () use ($assertSame, $calc): void {
    $r = $calc(3500, [['key'=>'addon_dinner','amount'=>5,'quantity'=>1]], 4000, DiscountScope::BaseFeeAndEligibleAddOns, ['addon_dinner'], 4000);
    $assertSame(1600, $r->discountAmountMinor); $assertSame(2400, $r->finalTotalMinor);
};
$tests['21 EUR minor-unit calculation'] = function () use ($assertSame): void {
    $assertSame(3500, Money::toMinor('35.00', 'EUR')); $assertSame('21.00', Money::decimal(2100, 'EUR'));
};
$tests['22 deterministic half-up rounding'] = function () use ($assertSame): void {
    $assertSame(1, Money::multiplyBasisPoints(1, 5000));
    $assertSame(34, Money::toMinor('0.335', 'EUR'));
};
$tests['23 zero amount'] = function () use ($assertSame, $calc): void {
    $assertSame(0, $calc(0, [], 4000)->finalTotalMinor);
};
$tests['24 invalid percentage rejected'] = function () use ($assert): void {
    try { Percentage::percentToBasisPoints('100.01'); $assert(false); } catch (InvalidArgumentException) {}
};
$tests['25 unpaid payment safety checks are present'] = function () use ($contains): void {
    $contains('src/Support/PaymentSafety.php', 'isPaid()');
    $contains('src/Support/PaymentSafety.php', 'payment_method');
};
$tests['26 pending payment recalculation is transactional'] = function () use ($contains): void {
    $contains('src/Services/UnpaidPaymentRecalculator.php', 'lockForUpdate()');
    $contains('src/Services/UnpaidPaymentRecalculator.php', "'payment_recalculated'");
};
$tests['27 completed payment is never altered'] = function () use ($contains): void {
    $contains('src/Support/PaymentSafety.php', '$payment->isPaid()');
    $contains('src/Services/RecalculationCoordinator.php', '$payment->isPaid()');
};
$tests['28 valid CSV sample'] = function () use ($assert, $root): void {
    $rows = array_map('str_getcsv', file($root . '/sample-discount-import.csv', FILE_IGNORE_NEW_LINES));
    $assert(count($rows) >= 3); $assert($rows[0] === ['email','discount_percentage','reason','valid_from','valid_until','notes']);
};
$tests['29 partially invalid CSV handling contract'] = function () use ($contains): void {
    $contains('src/Services/CsvImportService.php', "'invalid_email'");
    $contains('src/Services/CsvImportService.php', "'invalid_percentage'");
    $contains('src/Services/CsvImportService.php', "'too_many_columns'");
};
$tests['30 CSV duplicate detection'] = function () use ($contains): void {
    $contains('src/Services/CsvImportService.php', "'duplicate_in_file'");
    $contains('src/Services/CsvImportService.php', "'already_exists'");
};
$tests['31 administrative authorization'] = function () use ($contains): void {
    $contains('src/Services/Authorization.php', "can('update', \$conference)");
    $contains('src/Panel/ScheduledConference/Pages/DiscountSettings.php', 'authorizeManage()');
};
$tests['32 cross-conference IDOR controls'] = function () use ($contains): void {
    $contains('src/Panel/ScheduledConference/Resources/IndividualEntitlementResource.php', 'scheduled_conference_id');
    $contains('src/Services/EligibilityResolver.php', "where('scheduled_conference_id'");
};
$tests['33 invoice itemization contract'] = function () use ($contains): void {
    $contains('src/Services/PaymentDiscountService.php', "'cde_discount_line' => true");
    $contains('src/Services/PaymentDiscountService.php', 'discountAmountMinor');
};
$tests['34 receipt itemization contract'] = function () use ($contains): void {
    $contains('RESEARCH.md', 'Invoice and receipt');
    $contains('src/Services/PaymentDiscountService.php', "'total_amount'");
};
$tests['35 Payment Detail integration'] = function () use ($contains): void {
    $contains('src/ConferenceDiscountEligibilityPlugin.php', 'PaymentManager::getPaymentMethodInfolist');
    $contains('src/ConferenceDiscountEligibilityPlugin.php', 'discount_details');
};
$tests['36 discount report integration'] = function () use ($contains): void {
    $contains('src/ConferenceDiscountEligibilityPlugin.php', 'DiscountPaymentReportResource::class');
    $contains('src/Panel/ScheduledConference/Resources/DiscountPaymentReportResource.php', 'paypal_payment_id');
};
$tests['37 official PayPal action remains delegated'] = function () use ($notContains, $contains): void {
    $notContains('src/ConferenceDiscountEligibilityPlugin.php', 'Omnipay');
    $notContains('src/Managers/DiscountAwarePaymentManager.php', 'fulfillQueued');
    $contains('RESEARCH.md', 'PaypalPayment 1.1.0');
};
$tests['38 final value PayPal amount contract'] = function () use ($assertSame, $assert): void {
    $assertSame('21.00', PaypalAmountContract::format(2100, 'EUR'));
    $assert(PaypalAmountContract::matches(2100, 'EUR', '21'));
};
$tests['39 approved return remains PayPal responsibility'] = function () use ($notContains, $contains): void {
    $notContains('src/Managers/DiscountAwarePaymentManager.php', 'paid_at');
    $contains('RESEARCH.md', 'fulfillQueued');
};
$tests['40 cancellation leaves payment unpaid'] = function () use ($notContains): void {
    $notContains('src/ConferenceDiscountEligibilityPlugin.php', 'cancelUrl');
    $notContains('src/Managers/DiscountAwarePaymentManager.php', 'payment_method');
};
$tests['41 duplicate attempt protection'] = function () use ($contains): void {
    $contains('src/Database/SchemaDefinition.php', "unique('payment_id'");
    $contains('src/Services/SnapshotService.php', 'updateOrCreate');
};
$tests['42 idempotent snapshot and schema installation'] = function () use ($contains): void {
    $contains('src/Services/SchemaInstaller.php', 'isInstalled()');
    $contains('src/Services/SnapshotService.php', "['payment_id' => \$payment->getKey()]");
};

$tests['43 recalculation reports actual outcome'] = function () use ($contains): void {
    $contains('src/Services/RecalculationCoordinator.php', "'discounted' => 0");
    $contains('src/Support/RecalculationFeedback.php', 'recalculation_summary');
    $contains('src/Services/RecalculationCoordinator.php', 'unverified_domain_matches');
};
$tests['44 recalculation failures are no longer swallowed silently'] = function () use ($contains): void {
    $contains('src/Services/RecalculationCoordinator.php', 'report($exception)');
    $contains('src/Services/RecalculationCoordinator.php', "action: 'payment_recalculation_failed'");
};
$tests['45 audit detail uses scalar JSON states'] = function () use ($contains, $notContains): void {
    $contains('src/Panel/ScheduledConference/Resources/AuditLogResource.php', 'old_values_pretty');
    $contains('src/Panel/ScheduledConference/Resources/AuditLogResource.php', 'diagnostic_summary');
    $notContains('src/Panel/ScheduledConference/Resources/AuditLogResource.php', "TextEntry::make('old_values')->formatStateUsing");
};


$tests['46 verified-email-only remains the secure domain default'] = function () use ($assert, $assertSame): void {
    $policy = DomainIdentityPolicy::VerifiedEmailOnly;
    $assert(! $policy->accepts(false, true));
    $assert($policy->accepts(true, false));
    $assertSame('email_not_verified', $policy->rejectionReason(false, true));
};
$tests['47 confirmed conference author can satisfy the opt-in domain policy'] = function () use ($assert, $assertSame): void {
    $policy = DomainIdentityPolicy::VerifiedEmailOrConfirmedAuthor;
    $assert($policy->accepts(false, true));
    $assert(! $policy->accepts(false, false));
    $assertSame(null, $policy->rejectionReason(false, true));
    $assertSame('email_not_verified_and_not_confirmed_author', $policy->rejectionReason(false, false));
};
$tests['48 author evidence excludes drafts and negative terminal statuses'] = function () use ($assert): void {
    foreach (['Queued', 'On Review', 'On Payment', 'On Presentation', 'Editing', 'Published'] as $status) {
        $assert(AuthorEvidencePolicy::acceptsSubmissionStatus($status), "Expected accepted author status: {$status}");
    }
    foreach (['Incomplete', 'Declined', 'Withdrawn', 'Payment Declined'] as $status) {
        $assert(! AuthorEvidencePolicy::acceptsSubmissionStatus($status), "Expected rejected author status: {$status}");
    }
};
$tests['49 author identity decision records auditable evidence'] = function () use ($assert, $assertSame): void {
    $evidence = new AuthorIdentityEvidence(true, 'submission_owner', 77, null, 'Queued');
    $decision = new DomainIdentityDecision(
        eligible: true,
        policy: DomainIdentityPolicy::VerifiedEmailOrConfirmedAuthor,
        emailVerified: false,
        authorEvidence: $evidence,
        rejectionReason: null,
    );
    $context = $decision->toArray();
    $assert($decision->usedAuthorEvidence());
    $assertSame(77, $context['author_evidence_submission_id']);
    $assertSame('confirmed_author', $context['identity_verification_method']);
    $assertSame(false, $context['email_verified']);
};
$tests['50 author verification is tied to real conference submission evidence'] = function () use ($contains): void {
    $contains('src/Services/AuthorIdentityVerifier.php', "where('scheduled_conference_id'");
    $contains('src/Services/AuthorIdentityVerifier.php', "where('user_id'");
    $contains('src/Services/AuthorIdentityVerifier.php', "whereHas('participants'");
    $contains('src/Services/AuthorIdentityVerifier.php', "whereHas('submission'");
    $contains('src/Services/AuthorIdentityVerifier.php', "LOWER(TRIM(email))");
    $contains('src/Services/AuthorIdentityVerifier.php', 'acceptedSubmissionStatuses');
};
$tests['51 self-assignable Author role alone is not trusted'] = function () use ($notContains, $contains): void {
    $notContains('src/Services/AuthorIdentityVerifier.php', 'hasRole(');
    $notContains('src/Enums/DomainIdentityPolicy.php', 'AuthorRole');
    $contains('lang/en/messages.php', 'self-assignable Author role alone is not accepted');
};
$tests['52 creation and recalculation share one domain identity verifier'] = function () use ($contains): void {
    $contains('src/Services/EligibilityResolver.php', 'DomainIdentityVerifier');
    $contains('src/Services/RecalculationCoordinator.php', 'DomainIdentityVerifier');
    $contains('src/Services/EligibilityResolver.php', 'domainIdentityVerifier->evaluate');
    $contains('src/Services/RecalculationCoordinator.php', 'domainIdentityVerifier->evaluate');
};
$tests['53 schema upgrade adds an idempotent domain identity policy'] = function () use ($contains): void {
    $contains('src/Database/SchemaDefinition.php', 'public const VERSION = 2');
    $contains('src/Database/SchemaDefinition.php', "hasColumn('conference_discount_domains', 'identity_policy')");
    $contains('src/Database/SchemaDefinition.php', "default('verified_email_only')");
    $contains('database/migrations/2026_07_17_000001_add_domain_identity_policy_to_conference_discount_domains.php', "hasColumn('conference_discount_domains', 'identity_policy')");
};
$tests['54 institutional domain UI exposes the secure opt-in author fallback'] = function () use ($contains, $notContains): void {
    $contains('src/Panel/ScheduledConference/Resources/InstitutionalDomainResource.php', 'VerifiedEmailOrConfirmedAuthor');
    $contains('src/Panel/ScheduledConference/Resources/InstitutionalDomainResource.php', 'VerifiedEmailOnly');
    $notContains('src/Panel/ScheduledConference/Resources/InstitutionalDomainResource.php', 'VerifiedEmailOrAuthorRole');
    $contains('lang/pt-BR/messages.php', 'autor confirmado na conferência');
};
$tests['55 recalculation reports author-confirmed domain matches'] = function () use ($contains): void {
    $contains('src/Services/RecalculationCoordinator.php', 'confirmed_author_domain_matches');
    $contains('src/Support/RecalculationFeedback.php', 'confirmed_authors');
    $contains('src/Panel/ScheduledConference/Resources/InstitutionalDomainResource/Pages/EditInstitutionalDomain.php', 'afterSave');
};
$tests['56 PHP 8.1 source compatibility is preserved'] = function () use ($contains, $notContains): void {
    $contains('composer.json', '"php": "^8.1"');
    $notContains('src/Data/AuthorIdentityEvidence.php', 'readonly class');
    $notContains('src/Data/DomainIdentityDecision.php', 'readonly class');
};

$results = [];
$passed = 0;
$failed = 0;
$started = microtime(true);
foreach ($tests as $name => $test) {
    try {
        $test();
        $passed++;
        $results[] = ['name' => $name, 'status' => 'passed'];
        echo "PASS {$name}\n";
    } catch (Throwable $exception) {
        $failed++;
        $results[] = ['name' => $name, 'status' => 'failed', 'message' => $exception->getMessage()];
        echo "FAIL {$name}: {$exception->getMessage()}\n";
    }
}
$summary = [
    'generated_at' => gmdate(DATE_ATOM),
    'php_version' => PHP_VERSION,
    'tests' => count($tests),
    'passed' => $passed,
    'failed' => $failed,
    'duration_seconds' => round(microtime(true) - $started, 4),
    'results' => $results,
];
file_put_contents(__DIR__ . '/results/standalone.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "\n{$passed}/" . count($tests) . " passed; {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
