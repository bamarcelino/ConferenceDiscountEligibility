# Changelog

## 1.0.1 — 2026-07-17

- Fix the Audit Log detail page that returned HTTP 500 for JSON payloads.
- Show recalculation results instead of a generic success message, including matched, discounted, unchanged, skipped, paid, failed, and unverified-domain counts.
- Record recalculation failures in the audit trail and send the technical exception to the Laravel log instead of swallowing it silently.
- Distinguish recalculations that completed without an eligible discount.
- Add an exact-user warning because similar email addresses and different user IDs are intentionally not interchangeable.
- Execute recalculation toggles on rule edit forms instead of discarding them.
- Safely render nested and invalid-UTF-8 audit payloads as scalar JSON text.
- No schema changes; existing rules, payments, snapshots, and audit records are preserved.

## 1.0.0 — 2026-07-16

- Initial Leconfe 1.4.6 / PaypalPayment 1.1.0 release.
- Direct-user, exact-email, verified-domain, and CSV eligibility.
- Integer minor-unit calculation, snapshots, audit log, pending-payment recalculation, registration preview, Payment Detail section, invoice/receipt itemization, and dedicated discount report.
