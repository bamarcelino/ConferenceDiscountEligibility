# Validation Report - Conference Discount Eligibility 1.4.1

## Identification

- Date: 2026-09-09
- Plugin: Conference Discount Eligibility 1.4.1
- Primary target Leconfe: 1.5.1
- Preserved compatibility targets: Leconfe 1.5.0 and 1.4.6
- Target Paypal Payment plugin: 1.1.0
- Plugin schema version: 4

## Reported regression

A user testing an upgrade from Leconfe 1.5.0 to 1.5.1 reported HTTP 500 responses when opening a Scheduled Conference in either frontend or backend while Conference Discount Eligibility 1.4.0 was enabled.

## Root cause

Version 1.4.0 executed `CompatibilityGuard::assertCompatible()` during Scheduled Conference boot and accepted only Leconfe `1.4.6` and `1.5.0`. A valid Leconfe `1.5.1` installation was therefore rejected before plugin boot completed, producing an HTTP 500 on every affected Scheduled Conference request.

The failure was caused by the plugin's strict version allow-list, not by a detected breaking change in Leconfe's payment API.

## Leconfe 1.5.1 source review

The official Leconfe `1.5.0...1.5.1` comparison contains seven commits. The changes concern:

- static-page browser titles;
- Scheduled Conference announcement recipient scoping;
- contributor profile links;
- Unicode Scheduled Conference path decoding;
- Scheduled Conference Editor permission to view plugins;
- related translations, styles and tests.

`app/Managers/PaymentManager.php` is unchanged at the API boundary used by Conference Discount Eligibility. Leconfe 1.5.1 still exposes:

- `queue(model, paymentFee, user, type, title, requestUrl, description, amount, currency, expiredAt, additionalItems, baseAmount)`;
- `fulfillQueued(payment, paymentMethod, userId)`.

The plugin's existing reflection guards continue to verify these contracts at runtime.

## Version 1.4.1 changes

- Target Leconfe version changed to `1.5.1`.
- Supported versions changed to `1.4.6`, `1.5.0` and `1.5.1`.
- Runtime version simulation now accepts all three supported versions and still rejects an unsupported future `1.6.0` version.
- Manifest and release metadata updated to 1.4.1.
- Installation, upgrade and compatibility documentation updated.
- Package validation updated to require the 1.4.1 manifest and upgrade guide.

No payment calculation, coupon redemption, PayPal integration, zero-value settlement, authorization, persistence or schema logic was changed.

## Data compatibility

There is no schema migration in 1.4.1. Existing automatic rules, free-text reasons, institutional-domain rules, coupon campaigns, encrypted coupon codes, redemptions, payment snapshots, invoices, receipts, reports and audit records remain unchanged.

## Source-level validation completed

- Inspected the official Leconfe 1.5.1 release metadata.
- Compared official Leconfe tags 1.5.0 and 1.5.1.
- Inspected the Leconfe 1.5.1 `PaymentManager` contract used by this plugin.
- Confirmed that the reported failure is reproducible from the 1.4.0 version check: 1.5.1 was not in the supported version allow-list.
- Updated the compatibility runtime simulation to include 1.5.1.
- Retained signature guards so a future incompatible payment API is still rejected explicitly.

## Target validation still required

The following should be executed on an authenticated Leconfe 1.5.1 test installation before production rollout:

1. Upgrade from Conference Discount Eligibility 1.4.0 to 1.4.1.
2. Open a Scheduled Conference frontend page and confirm HTTP 200.
3. Open the Scheduled Conference backend and confirm HTTP 200.
4. Confirm Discount Eligibility navigation and settings load normally.
5. Create or edit one automatic discount rule.
6. Create or edit one coupon campaign.
7. Verify one Participant Payment and one Submission Payment.
8. Verify a positive-value PayPal flow with Paypal Payment 1.1.0.
9. Verify a 100% discount completes without opening PayPal.

## Conclusion

At the inspected source and API boundary, Conference Discount Eligibility 1.4.1 is adapted for Leconfe 1.5.1 while retaining 1.5.0 and 1.4.6 compatibility. The specific HTTP 500 reported after upgrading Leconfe to 1.5.1 is addressed by accepting the patch release after confirming that the payment contracts relied upon by the plugin are unchanged.

Authenticated end-to-end validation on the reporter's Leconfe 1.5.1 test environment remains the final acceptance step.
