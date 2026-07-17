# Conference Discount Eligibility

`Conference Discount Eligibility` is a scheduled-conference-scoped plugin for **Leconfe 1.4.6**. It applies server-side discounts to eligible participant-fee registrations before the native `Payment` is created. The official **Paypal Payment 1.1.0** plugin remains responsible for checkout, PayPal return/cancellation handling, transaction metadata, `paid_at`, receipts, and the native payment lifecycle.

## Included capabilities

- Direct eligibility by existing Leconfe user.
- Pending eligibility by exact email, with later user linking.
- Institutional-domain eligibility with boundary-safe domain and subdomain matching.
- Secure domain identity policy: verified email by default, with an explicit opt-in fallback for a confirmed conference author.
- Confirmed-author evidence tied to a real, submitted work in the same scheduled conference; the self-assignable `Author` role alone is never sufficient.
- CSV preview, dry run, validation, duplicate strategy, import report, and safe exports.
- Highest-percentage non-cumulative selection.
- Integer minor-unit calculation and basis-point percentages.
- Base-fee-only default, with optional explicitly eligible add-ons.
- Payment snapshot, evaluated-rule history, audit log, and safe unpaid recalculation.
- Payment Detail discount section and a dedicated Discount Payment Report.
- English, Brazilian Portuguese, and Spanish translations.
- No PayPal credentials, no PayPal reimplementation, and no core-file replacement.

## Package choice

Use `ConferenceDiscountEligibility-1.0.2.zip` in Leconfe's **Upload Plugin** action. Leconfe 1.4.6 accepts ZIP packages only. The `.tar.gz` is a supplemental distribution artifact and is not the panel-upload file.

## Validation status

Version 1.0.1 was installed in the real Leconfe 1.4.6 target. A direct-user recalculation changed an unpaid Participant Payment from **EUR 30.00 to EUR 18.00**, and Payment Detail, the audit trail, and invoice itemization were observed working in the live panel. The same installation also confirmed that the original domain rule rejected an unverified account with `email_not_verified`.

Version 1.0.2 adds the requested, opt-in confirmed-author fallback. Its isolated calculation/source-contract suite passed **56/56** scenarios, the entrypoint/signature smoke test passed, all PHP/Blade files passed `php -l`, and the runtime-file secret scan passed. The 1.0.2 author fallback has not yet been uploaded to the target panel, and PayPal Sandbox remains **PENDING EXTERNAL CREDENTIALS**. See `VALIDATION_REPORT.md` before production deployment.

## Documentation

- `RESEARCH.md`
- `ARCHITECTURE.md`
- `INSTALLATION.md`
- `CONFIGURATION.md`
- `SECURITY.md`
- `VALIDATION_REPORT.md`
- `CHANGELOG.md`
- `UPGRADE-1.0.2.md`

## 1.0.2 author-validation update

Existing and newly upgraded domain rules remain on **Verified email only**. An authorized administrator can opt a specific domain into **Verified email or confirmed conference author**. The fallback accepts an unverified account only when the same user or exact normalized email is tied to a submitted, non-rejected work in the current scheduled conference. This is intentionally stricter than merely checking whether the account selected the self-assignable Author role.
