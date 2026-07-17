# Conference Discount Eligibility

`Conference Discount Eligibility` is a scheduled-conference-scoped plugin for **Leconfe 1.4.6**. It applies server-side discounts to eligible participant-registration and submission fees before the native `Payment` is created. The official **Paypal Payment 1.1.0** plugin remains responsible for checkout, PayPal return/cancellation handling, transaction metadata, `paid_at`, receipts, and the native payment lifecycle.

## Included capabilities

- Direct eligibility by an existing Leconfe user.
- Pending eligibility by exact email, with later linking to the real user ID.
- Institutional-domain eligibility with boundary-safe exact/subdomain matching.
- Per-domain identity policy:
  - **Verified email only** — secure default;
  - **Verified email or confirmed conference author** — explicit opt-in.
- Confirmed-author fallback based on the exact user being either the owner of a submitted work or a linked submission participant with the `Author` role in the same scheduled conference.
- CSV preview, dry run, validation, duplicate strategy, import report, and safe exports.
- Highest-percentage non-cumulative selection.
- Integer minor-unit calculation and basis-point percentages.
- Base-fee-only default for every supported payment type, with optional explicitly eligible add-ons.
- Payment snapshot, evaluated-rule history, audit log, and safe unpaid recalculation.
- Payment Detail discount/identity section and a dedicated Discount Payment Report.
- English, Brazilian Portuguese, Portuguese, and Spanish translations.
- No PayPal credentials, no PayPal reimplementation, and no core-file replacement.

The self-assignable Leconfe `Author` account role alone is deliberately not treated as proof. Author fallback requires a concrete submission relationship in the current scheduled conference.

## Package choice

Use `ConferenceDiscountEligibility-1.1.0.zip` in Leconfe's **Upload Plugin** action. Leconfe 1.4.6 accepts ZIP packages only. The `.tar.gz` is supplied as a supplemental distribution artifact and is not the panel-upload file.

## Upgrade behavior

Version 1.1.0 keeps schema version 2 and requires no new database migration. It expands the discount engine and safe recalculation from participant payments to both native Leconfe payment types: participant and submission fees. The earlier `identity_policy` column remains unchanged. Existing domain rules remain on **Verified email only**. An administrator must explicitly enable author fallback for each intended domain.

## Validation status

Version 1.0.1 was installed in the real Leconfe 1.4.6 panel and successfully recalculated an unpaid participant payment from EUR 30.00 to EUR 18.00, updating Payment Detail, Audit Log, and Invoice 003. The author-confirmation path was also exercised in the target panel. Version 1.1.0 adds submission-fee coverage and has passed the isolated automated/source-contract, entrypoint, lint, package, and secret-scan checks recorded in `VALIDATION_REPORT.md`. Submission-fee discounting remains pending validation after upload to the target panel.

PayPal Sandbox remains **PENDING EXTERNAL CREDENTIALS**.

## Documentation

- `RESEARCH.md`
- `ARCHITECTURE.md`
- `INSTALLATION.md`
- `CONFIGURATION.md`
- `SECURITY.md`
- `UPGRADE-1.1.0.md`
- `VALIDATION_REPORT.md`
- `CHANGELOG.md`
