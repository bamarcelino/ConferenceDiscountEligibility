# Conference Discount Eligibility

`Conference Discount Eligibility` is a scheduled-conference-scoped plugin for **Leconfe 1.4.6, 1.5.0 and 1.5.1**. It applies server-side automatic eligibility discounts and secure payment-page coupons to both native Leconfe fee types: Participant Payment and Submission Payment. The official **Paypal Payment 1.1.0** plugin remains the only PayPal gateway and remains responsible for every positive-value checkout, return/cancellation processing, and PayPal transaction metadata. When a valid discount reduces the complete native Payment total to zero, the plugin completes that Payment through Leconfe's native `fulfillQueued()` method with `payment_method = full_discount`, without opening PayPal.

## Included capabilities

- Direct eligibility by existing Leconfe user.
- Pending eligibility by exact email, with later linking to the real user ID.
- Institutional-domain eligibility with boundary-safe exact/subdomain matching.
- Optional confirmed-author evidence for unverified institutional emails in the same scheduled conference.
- CSV preview, dry run, validation, duplicate strategy, import report, and safe exports.
- Coupon Campaigns with automatically generated or administrator-defined codes.
- Authorized administrators can reveal securely encrypted coupon codes again from the campaign list.
- Coupon percentage, reason, validity, global-use limit, per-user limit, native payment-type scope, and optional payment-fee restrictions.
- A required free-text reason written directly by the administrator, without predefined organization-specific choices.
- Coupon entry directly on unpaid Participant Payment and Submission Payment pages before the payment gateway is opened.
- Automatic zero-value completion for 100% discounts, with no PayPal checkout and with invoice, receipt, confirmation, snapshot, and audit preservation.
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
- The keyed hash is used for redemption lookup; an authenticated-encrypted copy is stored only for authorized administrative recovery.
- Generated, manually entered, and regenerated codes can be revealed again from Coupon Campaigns.
- Campaigns created before 1.3.1 retain their hash and remain valid, but must be regenerated once before their full code can be revealed.
- A coupon is reserved only when it wins against every other valid rule.
- A lower second coupon cannot replace an already reserved higher coupon.
- A coupon is consumed when Leconfe changes the payment to paid.
- Coupon changes are blocked after payment activity or PayPal transaction metadata appears.
- Completed payments are never repriced.

Rotating the Laravel `APP_KEY` invalidates existing coupon hashes. Export or replace active campaigns before an application-key rotation.

## Package choice

Use `ConferenceDiscountEligibility-1.4.1.zip` in Leconfe's **Upload Plugin** action. Leconfe accepts ZIP packages only.

## Upgrade behavior

Version 1.4.1 adds explicit compatibility with Leconfe 1.5.1 while preserving Leconfe 1.5.0 and 1.4.6 support. Leconfe 1.5.1 does not change the PaymentManager contracts used by the plugin; the previous HTTP 500 was caused by the plugin's strict version allow-list rejecting 1.5.1 during boot. No schema or stored data changes are required. See `UPGRADE-1.4.1.md`.

## Validation status

Version 1.4.1 was reviewed against the official Leconfe 1.5.1 source tag and the 1.5.0...1.5.1 core diff. The release does not change the discount, coupon, settlement, PayPal, or persistence logic; it extends the compatibility guard to accept 1.5.1 after confirming that the native `PaymentManager::queue()` and `PaymentManager::fulfillQueued()` APIs used by the plugin were unchanged in the patch release.

Authenticated end-to-end verification on a real Leconfe 1.5.1 installation is still recommended before production deployment.

## Documentation

- `RESEARCH.md`
- `ARCHITECTURE.md`
- `INSTALLATION.md`
- `CONFIGURATION.md`
- `SECURITY.md`
- `UPGRADE-1.4.1.md`
- `VALIDATION_REPORT.md`
- `CHANGELOG.md`

## Author

**Bruno Cesar Alves Marcelino**  
Author and Developer

Developed under **Scientia International**.
