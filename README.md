# Conference Discount Eligibility

`Conference Discount Eligibility` is a scheduled-conference-scoped plugin for **Leconfe 1.4.6**. It applies server-side discounts to eligible participant-fee registrations before the native `Payment` is created. The official **Paypal Payment 1.1.0** plugin remains responsible for checkout, PayPal return/cancellation handling, transaction metadata, `paid_at`, receipts, and the native payment lifecycle.

## Included capabilities

- Direct eligibility by existing Leconfe user.
- Pending eligibility by exact email, with later user linking.
- Verified institutional-domain eligibility with safe subdomain handling.
- CSV preview, dry run, validation, duplicate strategy, import report, and safe exports.
- Highest-percentage non-cumulative selection.
- Integer minor-unit calculation and basis-point percentages.
- Base-fee-only default, with optional explicitly eligible add-ons.
- Payment snapshot, evaluated-rule history, audit log, and safe unpaid recalculation.
- Payment Detail discount section and a dedicated Discount Payment Report.
- English, Brazilian Portuguese, and Spanish translations.
- No PayPal credentials, no PayPal reimplementation, and no core-file replacement.

## Package choice

Use `ConferenceDiscountEligibility-1.0.1.zip` in Leconfe's **Upload Plugin** action. Leconfe 1.4.6 accepts ZIP packages only. The `.tar.gz` is supplied as a requested supplemental distribution artifact and is not the panel-upload file.

## Validation status

The isolated calculation/source-contract suite passed **48/48** scenarios, the entrypoint/signature smoke test passed, all **94** PHP/Blade files passed `php -l`, and the runtime-file secret scan passed. The full Leconfe panel, database migrations, PHPUnit suite, Composer audit, and PayPal Sandbox flow could not be executed in the supplied environment because the complete deployment/vendor tree, production database/runtime details, Composer, and Sandbox credentials were not available. See `VALIDATION_REPORT.md` before production deployment.

## Documentation

- `RESEARCH.md`
- `ARCHITECTURE.md`
- `INSTALLATION.md`
- `CONFIGURATION.md`
- `SECURITY.md`
- `VALIDATION_REPORT.md`
- `CHANGELOG.md`

## 1.0.1 diagnostic hotfix

Version 1.0.1 preserves the 1.0.0 schema and adds visible recalculation statistics, safe failure logging, and a corrected Audit Log detail view. A direct entitlement is bound to the exact `users.id`; email addresses that differ by punctuation represent different accounts. Institutional-domain rules continue to require `email_verified_at`.
