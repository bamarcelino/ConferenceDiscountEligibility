# Validation Report - Conference Discount Eligibility 1.2.0

## Identification

- Date: 2026-07-17
- Plugin: Conference Discount Eligibility 1.2.0
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
- Plugin schema version: 3

## Scope of version 1.2.0

Version 1.2.0 adds Scheduled Conference coupon campaigns and a server-validated coupon field to unpaid Participant Payment and Submission Payment pages. It uses the official `PaymentManager::getPaymentMethodInfolist` extension point, keeps automatic user/email/domain discounts, applies only the highest valid percentage, and keeps Paypal Payment 1.1.0 as the only payment gateway.

New persistent structures:

- `conference_discount_coupons`;
- `conference_discount_coupon_redemptions`;
- `conference_discount_payment_snapshots.coupon_campaign_id`;
- `conference_discount_settings.coupon_redemption_enabled`.

## Commands executed

```text
php tests/run.php
php tests/smoke-entrypoint.php
php tests/payment-manager-runtime.php
php scripts/lint.php
php scripts/secret-scan.php
php scripts/validate-package.php <installable ZIP>
php scripts/validate-package.php <TAR.GZ>
unzip -t <installable ZIP>
unzip -t <source ZIP>
tar -tzf <TAR.GZ>
sha256sum -c ConferenceDiscountEligibility-1.2.0.sha256
```

The package and checksum commands are completed during the final packaging stage and their results are recorded in the external copy of this report and the SHA-256 manifest.

## Executed results before packaging

| Check | Result |
|---|---|
| Standalone unit/source/security scenarios | 100/100 passed; 0 failed; 0 skipped |
| Entrypoint and PaymentManager signature smoke test | Passed |
| Participant/submission payment-type smoke test | Passed |
| Runtime queue simulation | Passed for Participant Payment and Submission Payment; both converted EUR 25.00 to EUR 15.00 under a 40% discount |
| PHP/Blade syntax lint | 125 files checked; 0 failures |
| Secret/credential pattern scan | Passed |
| PHPUnit test methods authored | 25 methods |
| PHPUnit execution against full Laravel/Filament tree | NOT RUN - dependencies unavailable in the isolated build container |
| Composer audit | NOT RUN - Composer/package-network access unavailable |
| PHPStan/Psalm | NOT RUN - tools unavailable |

## Coupon scenarios covered by executed standalone tests

- normalization, restricted format, keyed hashing, masked hints, and cryptographic generation;
- scheduled-conference isolation;
- participant and submission payment scope;
- optional Payment Fee restrictions;
- inactive, future, expired, exhausted, wrong-type, and wrong-fee campaigns;
- global and per-user limits;
- transaction and pessimistic-lock source contracts;
- highest-percentage selection with direct-user, email, and domain rules;
- tie priority and non-cumulative behavior;
- prevention of downgrade by a lower second coupon;
- reservation, replacement, release, and consumption lifecycle;
- paid/initiated-payment immutability;
- native Payment amount and negative invoice-item update;
- restoration of the best automatic rule after removal;
- official Payment Detail infolist hook and nested Livewire component;
- Payment authorization, current-conference scoping, and rate limiting;
- audit records that exclude plaintext codes and code hashes;
- English, Brazilian Portuguese, Portuguese, and Spanish message coverage;
- schema upgrade, rollback ordering, and package manifest version.

## PayPal integration contract

- The coupon plugin never calls `fulfillQueued()`.
- It never marks a Payment paid and never implements a PayPal gateway.
- A winning coupon changes the native unpaid `Payment.amount` before checkout.
- Paypal Payment 1.1.0 remains responsible for creating checkout, validating amount/currency on return, recording PayPal metadata, and setting paid state.
- A Payment observer consumes an already reserved coupon only after Leconfe changes `paid_at`.

## Real target evidence inherited from earlier versions

The plugin family has been installed in the real Leconfe 1.4.6 target. Automatic discounts have been observed on Participant Payment and Submission Payment, including recalculation, Payment Detail, Audit Log, and invoice itemization. These observations validate the existing automatic-discount path, not the new 1.2.0 coupon UI.

## Target tests still required for 1.2.0

The following are not reported as completed until version 1.2.0 is uploaded to the authenticated target panel:

- schema version 3 installation against the production database driver;
- Coupon Campaign creation and one-time full-code display;
- Livewire coupon field rendering on Participant Payment and Submission Payment;
- invalid/expired/exhausted/wrong-fee browser cases;
- successful coupon reservation and invoice refresh;
- coupon removal and automatic-rule restoration;
- concurrent redemption behavior on the production database;
- PayPal Sandbox checkout receiving the coupon-adjusted amount;
- approved, canceled, failed, timeout, duplicate-return, receipt, `paid_at`, PayPal ID, and consumed-reservation checks.

PayPal Sandbox status: **PENDING EXTERNAL CREDENTIALS**.

## Known limitations and residual risks

1. Paypal Payment 1.1.0 does not persist an open-checkout marker before redirect. A short multi-tab window remains in which a user could open PayPal and then attempt to alter a still-unmarked Payment. The UI warns against this; changes are blocked as soon as payment method or PayPal completion metadata exists.
2. Coupon hashes use Laravel `APP_KEY`; rotating that key invalidates active code lookups.
3. The full coupon code cannot be recovered from the database and must be copied when created or regenerated.
4. Production database engine and exact PHP patch were not exposed by the panel, so database-engine-specific runtime behavior still requires target validation.

## Compatibility conclusion

At the inspected API boundary, version 1.2.0 is compatible with Leconfe 1.4.6 and Paypal Payment 1.1.0. The source passes all executed standalone, signature, runtime-simulation, lint, and secret-scan checks. Final production acceptance requires installation through the official panel and the authenticated browser/Sandbox checks listed above.

## Package checksum

The final archive checksums are published in `ConferenceDiscountEligibility-1.2.0.sha256`. The external distributed copy of this report is updated after archive creation with the installable ZIP checksum; an archive cannot safely embed its own final checksum without creating a self-referential hash.
