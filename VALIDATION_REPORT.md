# Validation Report - Conference Discount Eligibility 1.4.0

## Identification

- Date: 2026-09-02
- Plugin: Conference Discount Eligibility 1.4.0
- Primary target Leconfe: 1.5.0 (`bdf41d7d8ac58d12c5169c2efadb0dd9a5ad2ae3`)
- Preserved compatibility target: Leconfe 1.4.6 (`f7e369d`)
- Target Paypal Payment plugin: 1.1.0
- Target Paypal Payment tag commit: `6b2a0fc`
- Validation PHP runtime: 8.5.8 CLI
- Plugin PHP constraint: `^8.1`
- Target Laravel: 10.x
- Target Filament: 3.3.52
- Target Livewire: 3.8.1
- Validation operating system: Linux x86_64
- Production database driver: not exposed by the target panel
- Plugin schema version: 4

## Scope of version 1.4.0

Version 1.4.0 adds Leconfe 1.5.0 support while retaining compatibility with Leconfe 1.4.6. The free-text Reason field from 1.3.2, coupon-code recovery from 1.3.1, discovery behavior, coupon redemption, and zero-value completion remain unchanged.

The 1.4.0 implementation:

- follows the official Leconfe 1.4.6 and 1.5.0 discovery contract: one matching root folder with `index.yaml` and `index.php`;
- accepts the unchanged `PaymentManager::queue()` and `fulfillQueued()` contracts from both supported releases;
- resolves the Leconfe 1.5 payment-required notification through its public `paymentId` while preserving the legacy 1.4.6 participant/submission relation path;
- exposes `composer test` for Leconfe 1.5's plugin test discovery;
- declares `sitewide: true`, avoiding separate enabled records for administration, conference, and scheduled-conference URLs;
- initializes a missing sitewide enabled setting during `load()`, before Leconfe's direct boot-setting check, while retaining the normal disable toggle and preserving explicit `false`;
- registers operational resources only on the Scheduled Conference panel;
- supplies one required administrator-defined free-text Reason field with no predefined values;
- binds the native Filament `TextInput` directly to the existing `reason` attribute, without hidden state, hydration callbacks, update callbacks, or reactive dependencies;
- preserves all historical reason strings exactly as stored and requires no reason migration;
- keeps existing reason strings, including legacy CLAEC/Research4Life text, unchanged and editable without mapping or data loss;
- retains keyed hashes as the only coupon-redemption lookup and adds a separate encrypted recovery value;
- stores generated, manually entered, and regenerated codes through Laravel's authenticated encrypted cast;
- exposes decrypted codes only through an explicit authorized Coupon Campaign action;
- hides hashes and encrypted payloads from model serialization and audit history;
- preserves legacy campaigns with nullable recovery values and explains that their one-way hashes cannot reconstruct the original code;
- keeps positive-value Participant and Submission Payments under Paypal Payment 1.1.0;
- calls Leconfe's native `PaymentManager::fulfillQueued()` only for zero-value Payments;
- records `payment_method = full_discount` and `paid_at`;
- does not create PayPal identifiers or a PayPal checkout for zero totals;
- preserves invoice, receipt, negative discount line, snapshot, report, and audit information;
- consumes a reserved coupon through the existing paid-state observer;
- suppresses contradictory queued Payment Required notifications and sends Leconfe's native Payment Confirmed notification after commit;
- leaves the Payment pending when non-discounted add-ons produce a positive remainder.

Schema version 4 adds one nullable text column. Existing data is not rewritten.

## Commands executed

```text
php tests/run.php
php tests/smoke-entrypoint.php
php tests/plugin-discovery-runtime.php
php tests/payment-manager-runtime.php
php tests/notification-compatibility-runtime.php
php tests/leconfe-version-runtime.php
php scripts/lint.php
php scripts/secret-scan.php
scripts/build-release.sh
php scripts/validate-package.php artifacts/ConferenceDiscountEligibility-1.4.0.zip
unzip -t artifacts/ConferenceDiscountEligibility-1.4.0.zip
shasum -a 256 -c artifacts/ConferenceDiscountEligibility-1.4.0.sha256
```

## Executed source results

| Check | Result |
|---|---|
| Standalone unit/source/security scenarios | 127/127 passed; 0 failed; 0 skipped |
| Entrypoint and PaymentManager signature smoke test | Passed |
| Leconfe discovery/enablement runtime simulation | Passed; first discovery persisted true and explicit false remained false |
| Participant/submission payment-type smoke test | Passed |
| Runtime queue simulation | Passed for Participant and Submission Payments under 40% and 100% discounts |
| 1.4.6/1.5.0 notification compatibility simulation | Passed for participant, submission, pending, full-discount, and unrelated notifications |
| Leconfe version and PaymentManager guard simulation | Passed for 1.4.6 and 1.5.0; rejected 1.6.0 |
| 40% runtime result | EUR 25.00 became EUR 15.00 |
| 100% runtime delegation result | EUR 25.00 became EUR 0.00 and was delegated to zero-value settlement |
| PHP/Blade syntax lint | 134 files checked; 0 failures |
| Secret/credential pattern scan | Passed |
| PHPUnit test methods authored | 29 methods |
| PHPUnit execution against full Laravel/Filament tree | NOT RUN - full application dependency tree unavailable in the isolated build container |
| Composer audit | NOT RUN - Composer and a resolved plugin `composer.lock` were unavailable |
| PHPStan/Psalm | NOT RUN - tools unavailable |

## Leconfe 1.5 compatibility, discovery, reasons, and coupon recovery scenarios covered

- Leconfe core source at tags 1.4.6 and 1.5.0 was inspected for extraction, manifest parsing, entrypoint loading, registration, settings cache, panel routing, PaymentManager signatures, hooks, payment interfaces, metadata, roles, receipts, and notifications;
- Laravel 10, Filament 3.3.52, and Livewire 3.8.1 remain the relevant locked framework versions in both releases;
- release archive contains exactly one `ConferenceDiscountEligibility/` root;
- manifest folder matches the extracted root and declares version 1.4.0 plus sitewide enablement;
- entrypoint returns an `App\Classes\Plugin` instance and reports a clear error for source archives without the release autoloader;
- a missing enabled setting resolves to enabled, and the standard toggle can persist a global disabled state;
- one native required 255-character `TextInput` is bound directly to `reason`;
- the shared form contains no predefined Select, Hidden field, reactive state, hydration callback, update callback, or non-dehydrated state;
- existing CLAEC/Research4Life, generic, and arbitrary historical values remain unchanged because no mapping is performed;
- all five shipped locale directories contain the free-text help message and no longer ship the removed preset labels;
- schema upgrade adds nullable encrypted recovery without changing existing hashes or redemptions;
- new and regenerated codes populate hash, hint, and encrypted value;
- reveal action decrypts only on administrator request and handles decryption failure;
- legacy rows expose an explanatory unavailable action;
- audit payloads exclude both hash and encrypted value.

## Inherited 100% discount scenarios covered

- 100% base fee without add-ons produces final total zero;
- base-fee-only scope preserves a positive add-on remainder;
- eligible add-ons may also be reduced to zero when configured;
- negative totals are rejected;
- positive totals are not auto-completed;
- new Participant and Submission Payments delegate zero totals after snapshot creation;
- coupon reservations are persisted before zero-value completion;
- coupon removal can complete the Payment when a remaining automatic 100% rule wins;
- explicit recalculation and native fee-edit snapshot reapplication handle zero totals;
- native `fulfillQueued()` is used with `full_discount`;
- no Omnipay or PayPal metadata is created by the settlement service;
- invoice and receipt generation paths are preserved;
- Payment Required notifications are suppressed only after full-discount completion;
- native Payment Confirmed notification is scheduled after commit;
- payment-page messaging explains that no gateway is required.

## PayPal boundary

Paypal Payment 1.1.0 reads `Payment.amount` and always attempts to create a PayPal purchase. The inherited zero-value settlement prevents a zero-value Payment from reaching the gateway. Positive totals, including positive add-on remainders after a 100% base discount, continue to use the official PayPal flow.

The plugin does not implement PayPal checkout, returns, cancellation, credentials, or PayPal transaction identifiers.

## Real target evidence inherited from earlier versions

The plugin family has been installed in the real Leconfe 1.4.6 target. Automatic discounts have been observed on Participant and Submission Payments, including recalculation, Payment Detail, Audit Log, and invoice itemization. Coupon 1.2.0 and zero-value settlement 1.2.1 still require authenticated target-panel execution.

## Target tests still required

- upload of version 1.4.0 through a Leconfe 1.5.0 panel, confirming schema version 4 and immediate installed/enabled display;
- creation and repeat reveal of generated and manually entered codes;
- legacy campaign behavior before and after regeneration;
- ordinary navigation to a Scheduled Conference, confirming the Discount Eligibility menu appears;
- create/edit validation of the required free-text Reason field for individual, email, institutional-domain, and coupon records, including an existing legacy value;
- application of a 100% coupon to an unpaid Participant Payment;
- application of a 100% coupon to an unpaid Submission Payment;
- verification of `paid_at`, `payment_method = full_discount`, receipt, invoice, and consumed coupon;
- confirmation that no PayPal action remains visible for the completed zero-value Payment;
- confirmation that no Payment Required email/database notification is delivered;
- confirmation that Payment Confirmed is delivered;
- 100% base-only coupon with a positive add-on remainder reaching PayPal with only that remainder;
- concurrent 100% redemption behavior on the production database;
- PayPal Sandbox positive-remainder flow.

PayPal Sandbox status: **PENDING EXTERNAL CREDENTIALS**.

## Known limitations and residual risks

1. Full Laravel/Filament integration tests were not executed in the isolated build container.
2. The production database engine is not exposed by the panel, so engine-specific locking behavior still needs target validation.
3. Paypal Payment 1.1.0 does not persist a checkout-start marker before redirect. This limitation remains relevant only for positive-value payments.
4. The native Payment Confirmed notification uses Leconfe's existing English template; plugin UI messages are translated in English, Portuguese, Brazilian Portuguese, and Spanish.
5. Leconfe builds Filament panel routes before a Livewire upload action completes. The installed-plugins table updates immediately; newly registered navigation is expected on the next ordinary panel request/navigation, without a direct plugin URL.

## Compatibility conclusion

At the inspected API boundary, version 1.4.0 is compatible with Leconfe 1.5.0 and 1.4.6 plus Paypal Payment 1.1.0. All executed standalone, signature, version-guard, notification, discovery, queue, lint, secret-scan, free-text-reason, coupon-recovery, and archive-structure checks passed. Final acceptance on Leconfe 1.5.0 requires the authenticated target tests listed above.

## Package checksum

The final installable archive checksum is published in `artifacts/ConferenceDiscountEligibility-1.4.0.sha256`. The report embedded inside the archive cannot safely contain the archive's own final hash.
