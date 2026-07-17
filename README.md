# Conference Discount Eligibility

`Conference Discount Eligibility` is a scheduled-conference-scoped plugin for **Leconfe 1.4.6**. It applies server-side automatic eligibility discounts and secure payment-page coupons to both native Leconfe fee types: Participant Payment and Submission Payment. The official **Paypal Payment 1.1.0** plugin remains the only PayPal gateway and remains responsible for checkout, return/cancellation processing, transaction metadata, `paid_at`, receipts, and the native payment lifecycle.

## Included capabilities

- Direct eligibility by existing Leconfe user.
- Pending eligibility by exact email, with later linking to the real user ID.
- Institutional-domain eligibility with boundary-safe exact/subdomain matching.
- Optional confirmed-author evidence for unverified institutional emails in the same scheduled conference.
- CSV preview, dry run, validation, duplicate strategy, import report, and safe exports.
- Coupon Campaigns with automatically generated or administrator-defined codes.
- Coupon percentage, reason, validity, global-use limit, per-user limit, native payment-type scope, and optional payment-fee restrictions.
- Coupon entry directly on unpaid Participant Payment and Submission Payment pages before the payment gateway is opened.
- Server-side coupon validation, attempt throttling, transactional reservation, release, replacement, and consumption.
- Highest-percentage non-cumulative selection across automatic rules and coupons.
- Integer minor-unit calculation and basis-point percentages.
- Base-fee-only default, with optional explicitly eligible add-ons.
- Payment snapshots, evaluated-rule history, audit log, safe unpaid recalculation, and coupon status tracking.
- Payment Detail sections, invoice/receipt-compatible negative discount line, and Discount Payment Report.
- English, Brazilian Portuguese, Portuguese, and Spanish translations.
- No PayPal credentials, PayPal reimplementation, core-file replacement, or event-specific hardcoding.

The self-assignable Leconfe `Author` account role alone is not treated as proof. Confirmed-author fallback requires a concrete same-conference submission relationship.

## Coupon security model

- Full codes are normalized and keyed-hashed with the Laravel application key.
- Only the hash and a masked hint are stored.
- A generated or regenerated full code is displayed once to the administrator.
- A coupon is reserved only when it wins against every other valid rule.
- A lower second coupon cannot replace an already reserved higher coupon.
- A coupon is consumed when Leconfe changes the payment to paid.
- Coupon changes are blocked after payment activity or PayPal transaction metadata appears.
- Completed payments are never repriced.

Rotating the Laravel `APP_KEY` invalidates existing coupon hashes. Export or replace active campaigns before an application-key rotation.

## Package choice

Use `ConferenceDiscountEligibility-1.2.0.zip` in Leconfe's **Upload Plugin** action. Leconfe 1.4.6 accepts ZIP packages only. The `.tar.gz` is a supplemental archive and is not the panel-upload file.

## Upgrade behavior

Version 1.2.0 upgrades the plugin schema to version 3 and creates coupon campaign and coupon redemption tables. Existing eligibility rules, payments, snapshots, invoices, and audit logs are preserved. See `UPGRADE-1.2.0.md`.

## Validation status

The automatic discount path has already been exercised successfully in the real target installation, including participant and submission amounts, Payment Detail, Audit Log, and invoice output. Version 1.2.0 has been subjected to the isolated tests, source-contract checks, entrypoint/runtime simulations, syntax lint, secret scan, and archive extraction checks recorded in `VALIDATION_REPORT.md`.

The coupon UI and coupon database migration still require installation and end-to-end validation in the authenticated target panel. PayPal Sandbox remains **PENDING EXTERNAL CREDENTIALS**.

## Documentation

- `RESEARCH.md`
- `ARCHITECTURE.md`
- `INSTALLATION.md`
- `CONFIGURATION.md`
- `SECURITY.md`
- `UPGRADE-1.2.0.md`
- `VALIDATION_REPORT.md`
- `CHANGELOG.md`
