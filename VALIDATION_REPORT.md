# Validation Report - Conference Discount Eligibility 1.2.1

## Identification

- Date: 2026-07-17
- Plugin: Conference Discount Eligibility 1.2.1
- Target Leconfe: 1.4.6
- Target Leconfe tag commit: `f7e369d`
- Target Paypal Payment plugin: 1.1.0
- Target Paypal Payment tag commit: `6b2a0fc`
- Validation PHP runtime: 8.4.16 CLI
- Plugin PHP constraint: `^8.1`
- Target Laravel: 10.x
- Target Filament: 3.3.52
- Target Livewire: 3.8.1
- Validation operating system: Linux x86_64
- Production database driver: not exposed by the target panel
- Plugin schema version: 3 - unchanged from 1.2.0

## Scope of version 1.2.1

Version 1.2.1 adds safe automatic completion when a valid automatic discount or coupon reduces the complete native Payment total to exactly zero.

The implementation:

- keeps positive-value Participant and Submission Payments under Paypal Payment 1.1.0;
- calls Leconfe's native `PaymentManager::fulfillQueued()` only for zero-value Payments;
- records `payment_method = full_discount` and `paid_at`;
- does not create PayPal identifiers or a PayPal checkout for zero totals;
- preserves invoice, receipt, negative discount line, snapshot, report, and audit information;
- consumes a reserved coupon through the existing paid-state observer;
- suppresses contradictory queued Payment Required notifications and sends Leconfe's native Payment Confirmed notification after commit;
- leaves the Payment pending when non-discounted add-ons produce a positive remainder.

No database migration is introduced.

## Commands executed

```text
php tests/run.php
php tests/smoke-entrypoint.php
php tests/payment-manager-runtime.php
php scripts/lint.php
php scripts/secret-scan.php
php scripts/validate-package.php ConferenceDiscountEligibility-1.2.1.zip
php scripts/validate-package.php ConferenceDiscountEligibility-1.2.1.tar.gz
unzip -t ConferenceDiscountEligibility-1.2.1.zip
unzip -t ConferenceDiscountEligibility-1.2.1-source.zip
tar -tzf ConferenceDiscountEligibility-1.2.1.tar.gz
sha256sum -c ConferenceDiscountEligibility-1.2.1.sha256
```

## Executed source results

| Check | Result |
|---|---|
| Standalone unit/source/security scenarios | 114/114 passed; 0 failed; 0 skipped |
| Entrypoint and PaymentManager signature smoke test | Passed |
| Participant/submission payment-type smoke test | Passed |
| Runtime queue simulation | Passed for Participant and Submission Payments under 40% and 100% discounts |
| 40% runtime result | EUR 25.00 became EUR 15.00 |
| 100% runtime delegation result | EUR 25.00 became EUR 0.00 and was delegated to zero-value settlement |
| PHP/Blade syntax lint | 128 files checked; 0 failures |
| Secret/credential pattern scan | Passed |
| PHPUnit test methods authored | 28 methods |
| PHPUnit execution against full Laravel/Filament tree | NOT RUN - full application dependency tree unavailable in the isolated build container |
| Composer audit | NOT RUN - Composer and a resolved plugin `composer.lock` were unavailable |
| PHPStan/Psalm | NOT RUN - tools unavailable |

## 100% discount scenarios covered

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

Paypal Payment 1.1.0 reads `Payment.amount` and always attempts to create a PayPal purchase. Version 1.2.1 therefore prevents a zero-value Payment from reaching the gateway. Positive totals, including positive add-on remainders after a 100% base discount, continue to use the official PayPal flow.

The plugin does not implement PayPal checkout, returns, cancellation, credentials, or PayPal transaction identifiers.

## Real target evidence inherited from earlier versions

The plugin family has been installed in the real Leconfe 1.4.6 target. Automatic discounts have been observed on Participant and Submission Payments, including recalculation, Payment Detail, Audit Log, and invoice itemization. Coupon 1.2.0 and zero-value settlement 1.2.1 still require authenticated target-panel execution.

## Target tests still required

- upload and activation of version 1.2.1 through the Leconfe panel;
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

## Compatibility conclusion

At the inspected API boundary, version 1.2.1 remains compatible with Leconfe 1.4.6 and Paypal Payment 1.1.0. All executed standalone, signature, runtime-simulation, lint, secret-scan, and archive-structure checks passed. Final acceptance of the 100% path requires the authenticated target tests listed above.

## Package checksum

The final archive checksums are published in `ConferenceDiscountEligibility-1.2.1.sha256`. The separately distributed validation report may include the installable ZIP checksum after archive creation; the report embedded inside the archive cannot safely contain the archive's own final hash.
