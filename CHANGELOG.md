# Changelog

## 1.0.2 — 2026-07-17

- Add a per-domain identity policy with a secure `verified_email_only` default.
- Add the opt-in `verified_email_or_confirmed_author` policy requested after live validation.
- Confirm author status from real submission evidence in the same scheduled conference: submission owner, linked Author participant, or exact normalized author-list email.
- Exclude drafts, declined, payment-declined, and withdrawn submissions from author evidence.
- Do not trust the self-assignable Leconfe Author role by itself.
- Record author-evidence source, submission ID, author ID, submission status, policy, and verification method in evaluated-rule audit/snapshot metadata.
- Report author-confirmed domain matches separately during unpaid-payment recalculation.
- Add an idempotent schema v2 upgrade for the domain identity policy.
- Preserve PHP 8.1 source compatibility.

## 1.0.1 — 2026-07-17

- Fix the Audit Log detail HTTP 500 by rendering JSON values through safe scalar presentation fields.
- Show recalculation results instead of a generic success message, including matched, discounted, unchanged, skipped, paid, failed, and unverified-domain counts.
- Record and report recalculation failures rather than swallowing them.
- Make edit-form recalculation toggles operational.
- Add exact-user warnings because similar email addresses and different user IDs are intentionally not interchangeable.

## 1.0.0 — 2026-07-16

- Initial implementation.
- Direct-user, exact-email, verified-domain, and CSV eligibility.
- Server-side participant-fee discounting, audit snapshots, unpaid recalculation, Payment Detail, invoice/receipt itemization, and dedicated report.
