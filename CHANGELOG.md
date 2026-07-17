# Changelog

## 1.2.0 - 2026-07-17 - Coupon Campaigns and Payment-Page Redemption

- Added Scheduled Conference **Coupon Campaigns** administration.
- Added secure automatic code generation and custom code entry.
- Added keyed coupon-code hashing; full codes are never stored in plaintext.
- Added coupon validity, global usage limits, per-user limits, native payment-type scope, and optional payment-fee scope.
- Added a Livewire coupon field to unpaid Participant Payment and Submission Payment pages through the official `PaymentManager::getPaymentMethodInfolist` hook.
- Added server-side rate limiting and cross-conference/payment authorization checks.
- Added transactional coupon reservation, release, replacement, and paid-payment consumption.
- Added selection with existing automatic rules; discounts remain non-cumulative and the highest percentage wins.
- Prevented a lower second coupon from replacing an existing higher reserved coupon.
- Added coupon snapshots, invoice/receipt discount lines, Payment Detail state, settings toggle, and audit events.
- Added schema version 3 and idempotent migration/rollback support.
- Added English, Portuguese, Brazilian Portuguese, and Spanish coupon translations.
- Kept PayPal checkout, return validation, payment completion, and transaction metadata fully delegated to Paypal Payment 1.1.0.

## 1.1.0 — 2026-07-17 — Participant and Submission Payments

- Expanded automatic discount creation from participant fees to both native Leconfe 1.4.6 payment types: participant and submission fees.
- Expanded safe unpaid-payment recalculation for direct-user, exact-email, and institutional-domain rules to both payment types.
- Preserved the same server-side minor-unit calculation, snapshot, negative invoice line, audit trail, and official PayPal flow for submission fees.
- Added a centralized allow-list so unknown future payment types remain untouched.
- Added payment-type visibility to the dedicated Discount Payment Report and CSV export.
- Updated interface text and documentation in English, Portuguese, Brazilian Portuguese, and Spanish.
- No database migration or core-file modification is required.

## 1.0.3 — 2026-07-17

- Added exact, case-insensitive email matching against the author list of an eligible submission in the same scheduled conference.
- Preserved submission-owner and linked participant/Author evidence.
- Continued to reject the global self-assignable Author role when it is not backed by a submission.
- Added source-contract and standalone tests for `submission_author_email` evidence.
- Documented Chrome/Livewire session isolation for administrators testing multiple accounts in parallel.

## 1.0.2 — 2026-07-17

- Added per-domain identity verification policies.
- Preserved verified-email-only as the secure default for all existing rules.
- Added an explicit confirmed-author fallback for the same scheduled conference.
- Accepted only concrete submission ownership or participant/Author linkage; self-assigned Author role and author-email metadata alone are not proof.
- Added schema version 2 and an idempotent `identity_policy` column.
- Added author-evidence snapshots, Payment Detail/report visibility, CSV report field, audit context, and recalculation statistics.
- Added English, Portuguese, Brazilian Portuguese, and Spanish interface text.
- Added source-contract and standalone tests for identity policy, status gating, conference scoping, and evidence preservation.

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
