# Conference Discount Eligibility

`Conference Discount Eligibility` is a scheduled-conference-scoped plugin for **Leconfe 1.4.6**. It applies server-side automatic eligibility discounts and secure payment-page coupons to both native Leconfe fee types: Participant Payment and Submission Payment. The official **Paypal Payment 1.1.0** plugin remains the only PayPal gateway and remains responsible for every positive-value checkout, return/cancellation processing, and PayPal transaction metadata. When a valid discount reduces the complete native Payment total to zero, the plugin completes that Payment through Leconfe's native `fulfillQueued()` method with `payment_method = full_discount`, without opening PayPal.

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

Use `ConferenceDiscountEligibility-1.3.2.zip` in Leconfe's **Upload Plugin** action. Leconfe 1.4.6 accepts ZIP packages only.

## Upgrade behavior

Version 1.3.2 replaces the Reason selector with one required free-text field and does not change the schema. Existing reasons remain unchanged and appear directly in the field when a record is edited. The schema version 4 encrypted coupon-code recovery introduced in 1.3.1 remains included. See `UPGRADE-1.3.2.md`.

## Validation status

The automatic discount path has already been exercised successfully in the real target installation, including participant and submission amounts, Payment Detail, Audit Log, and invoice output. Version 1.3.2 has been subjected to the isolated tests, source-contract checks, Leconfe 1.4.6 discovery review, entrypoint/runtime simulations, syntax lint, secret scan, and archive extraction checks recorded in `VALIDATION_REPORT.md`.

The corrected free-text Reason form still requires end-to-end validation in the authenticated target panel. PayPal Sandbox remains **PENDING EXTERNAL CREDENTIALS**.

## Documentation

- `RESEARCH.md`
- `ARCHITECTURE.md`
- `INSTALLATION.md`
- `CONFIGURATION.md`
- `SECURITY.md`
- `UPGRADE-1.3.2.md`
- `VALIDATION_REPORT.md`
- `CHANGELOG.md`

## Author

**Bruno Cesar Alves Marcelino**  
Author and Developer

Developed under **Scientia International**.
