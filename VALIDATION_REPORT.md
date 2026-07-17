# Validation Report — Conference Discount Eligibility 1.1.0

## Identification

- Date: 2026-07-17
- Plugin: Conference Discount Eligibility 1.1.0
- Target Leconfe: 1.4.6
- Target Leconfe tag commit: `f7e369d`
- Target Paypal Payment plugin: 1.1.0
- Target Paypal Payment tag commit: `6b2a0fc`
- Validation PHP runtime: 8.4.16 CLI
- Target PHP constraint: `^8.1`
- Target Filament: 3.3.52
- Target Livewire: 3.8.1
- Validation OS: Linux x86_64
- Production database driver: not exposed by the target panel

## Scope of this release

Version 1.1.0 expands the existing server-side discount pipeline to both native Leconfe 1.4.6 payment types:

- `TYPE_PARTICIPANT_FEE = 1`
- `TYPE_SUBMISSION_FEE = 2`

Unknown future payment types remain delegated unchanged to the Leconfe core. No new database migration or external dependency was introduced.

## Commands executed

```text
php tests/run.php
php tests/smoke-entrypoint.php
php tests/payment-manager-runtime.php
php scripts/lint.php
php scripts/secret-scan.php
php scripts/validate-package.php ConferenceDiscountEligibility-1.1.0.zip
unzip -t ConferenceDiscountEligibility-1.1.0.zip
```

## Results

| Check | Result |
|---|---|
| Standalone and source-contract tests | 68/68 passed |
| Entrypoint and method-signature smoke test | Passed |
| Participant/submission type allow-list smoke test | Passed |
| Runtime queue simulation | Passed for participant and submission types; both converted a EUR 25.00 base to EUR 15.00 under a 40% prepared discount |
| PHP/Blade lint | 105 files checked; 0 failures |
| Secret scan | Passed; no credential-like runtime material detected |
| Package extraction and root validation | Passed |
| Duplicate nested plugin directory | Not present |
| `index.php` and `index.yaml` location | Correct |
| Schema migration for 1.1.0 | None required |

## Functional contracts validated

- Participant and submission payment creation both pass through `DiscountAwarePaymentManager`.
- The final amount is passed to the native parent `PaymentManager::queue()`.
- Original base amount and a negative discount item remain available to invoice/receipt rendering.
- Safe recalculation searches both participant and submission payments.
- Paid payments, payments with a payment method, and payments with PayPal completion metadata remain protected.
- Direct-user, exact-email, and domain eligibility use the same resolution and highest-percentage rule for both payment types.
- Domain author evidence remains scoped to the same scheduled conference and accepted submission states.
- The dedicated discount report and CSV identify whether the payment is a participant registration or a submission.
- Paypal Payment is not modified and remains responsible for checkout, completion, `paid_at`, payment method, and transaction metadata.

## Real target evidence inherited from earlier releases

The plugin was installed in the real Leconfe 1.4.6 target panel. Version 1.0.1 successfully recalculated an unpaid participant payment from EUR 30.00 to EUR 18.00, updated Payment Detail and Audit Log, and regenerated Invoice 003 with a negative EUR 12.00 discount line. The domain confirmed-author policy and audit details were also exercised in the target panel in later updates.

## Pending target validation for 1.1.0

The following must not be reported as completed until observed after uploading this release:

- creation of a new discounted Submission Payment in the real panel;
- recalculation of the existing unpaid Submission Payment #2;
- regenerated submission invoice with a negative discount line;
- PayPal checkout receiving the discounted submission amount;
- PayPal Sandbox approval/cancellation/duplicate-return behavior;
- final receipt and Payment Report after a completed Sandbox transaction.

PayPal Sandbox status: **PENDING EXTERNAL CREDENTIALS**.

## Tools not available in the build container

- Composer was not installed, so `composer audit` was not executed in this build container.
- PHPUnit/Pest dependencies were not installed; the repository's standalone tests and runtime simulations were executed instead.
- PHPStan/Psalm was not installed.
- The target panel upload and authenticated browser flow cannot be executed from the isolated build container.

No claim is made that these unexecuted checks passed.

## Known operational limitation

Paypal Payment 1.1.0 does not persist an open-checkout marker before redirect. An administrator must confirm that no PayPal checkout is open before recalculating any unpaid participant or submission payment.

## Compatibility conclusion

The source and package are compatible at the inspected API boundary with Leconfe 1.4.6 and Paypal Payment 1.1.0. The participant path is already evidenced in the target installation. Submission-fee coverage is implemented and locally validated but requires one final real-panel recalc/payment test before production acceptance.
