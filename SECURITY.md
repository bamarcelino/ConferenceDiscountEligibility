# Security Review

## Implemented controls

- Scheduled-conference scoping on administrative resources, campaign lookups, Payment Fee validation, coupon reservations, and snapshots.
- Native Payment view policy on every coupon component request.
- Scheduled-conference update authorization on every administrative page/resource/action.
- Server-only eligibility, percentage selection, and monetary calculation.
- Integer minor-unit arithmetic and bounded basis points.
- Exact email normalization and boundary-safe domain matching.
- Verified email as the default institutional-domain identity policy.
- Optional author fallback requires concrete same-conference submission evidence; global self-assigned Author role alone is rejected.
- Coupon codes normalized to a restricted alphabet and length.
- Generated coupon codes use `random_bytes(16)`.
- Full coupon codes stored only as keyed HMAC-SHA256 hashes; only masked hints remain visible.
- Full generated/regenerated code displayed once to the authorized administrator.
- Livewire coupon attempts rate-limited by user, Payment, and hashed IP context.
- Payment, campaign, redemptions, and usage rows protected with transactions and pessimistic locks.
- One redemption row per Payment enforced by a unique constraint.
- Per-user and global-use limits validated under lock.
- Existing reserved coupon included when a replacement code is evaluated, preventing downgrade by a lower code.
- Paid, payment-method, and PayPal-metadata checks before any coupon or recalculation change.
- Coupon reservation consumed only after native `paid_at` changes.
- Eloquent fillable fields and server-assigned scheduled-conference/actor IDs.
- Private CSV storage, extension/MIME/size/row validation, no formula execution, and safe CSV exports.
- Audit log excludes coupon hash and full coupon value.
- No PayPal credentials, card data, production tokens, or real personal data in the package.
- No Leconfe core or PaypalPayment file replacement.

## Threat review

| Threat | Control |
|---|---|
| IDOR / cross-conference access | current-conference reload plus native Payment view policy and scoped admin queries |
| Privilege escalation | scheduled-conference update authorization |
| CSRF | authenticated Filament/Livewire request lifecycle |
| SQL injection | Eloquent/query builder and fixed server-side predicates |
| XSS | escaped Blade/Filament output; reasons and notes rendered as text |
| Mass assignment | explicit fillable attributes and server mutation |
| Domain spoofing | exact suffix-boundary matching plus identity policy |
| Author-role impersonation | concrete same-conference submission evidence required |
| Coupon guessing | 128-bit generated randomness, keyed hashes, and rate limits |
| Coupon database disclosure | no plaintext code storage; HMAC key remains in application configuration |
| Coupon replay | unique Payment reservation, per-user/global limits, status lifecycle |
| Concurrent over-redemption | transaction and row locks on campaign/claims/payment |
| Lower-code downgrade | current reserved coupon participates in winner selection |
| Browser amount tampering | browser submits code only; amount and percentage resolved on server |
| Repricing a paid/started payment | PaymentSafety rejects paid/method/PayPal-metadata states |
| Duplicate coupon consumption | status transition is idempotent and only reserved rows are consumed |
| CSV abuse | private storage, parser limits, validation, and formula-safe export |
| Secret disclosure | no gateway secrets read or logged; code hashes excluded from audit |
| Audit tampering | no audit edit/delete UI; database administration remains the privileged boundary |

## Application-key dependency

Coupon hashes are keyed by Laravel `APP_KEY`. Rotating that key invalidates existing coupon lookups. Before an intentional key rotation, replace active campaigns and redistribute new codes. The plugin does not retain recoverable plaintext codes by design.

## Payment checkout race

PaypalPayment 1.1.0 does not persist a checkout-start marker before redirect. A user could open PayPal and then, in another tab, attempt to change a coupon before any PayPal metadata exists. The UI warns against this, and all changes are blocked once `payment_method` or PayPal completion metadata is present. The remaining pre-return multi-tab window is a known limitation of the upstream gateway lifecycle.

## Domain author-fallback risk decision

`verified_email_or_confirmed_author` is weaker than verified email and must be enabled per domain. It binds evidence to the exact user, current scheduled conference, concrete submission relation, and allowed status. Keep `verified_email_only` where email verification is operational.

## Residual risks and unexecuted checks

- Real target-panel migration, Livewire rendering, and browser behavior require installation in the authenticated Leconfe environment.
- PayPal Sandbox completion requires secure external credentials and remains pending.
- The production database driver and exact PHP runtime were not supplied.
- Composer and package-network access were unavailable in the build container, so `composer audit` was **NOT RUN**, not passed.
- PHPStan/Psalm and full PHPUnit/Pest integration against the complete Leconfe vendor tree were not executable in the available container.

## Zero-value payment settlement

- Settlement is allowed only when the calculated and persisted totals are exactly zero.
- Negative totals are rejected.
- The Payment row is locked and `PaymentSafety` rejects paid, initiated, or PayPal-metadata-bearing payments.
- The plugin calls Leconfe's native `fulfillQueued()` and never fabricates PayPal identifiers.
- `full_discount` is recorded as the payment method; `gateway_required` is false in plugin metadata.
- Payment-required notifications are suppressed only after the same Payment is already paid through `full_discount`.
- The confirmation notification is scheduled after commit to avoid notifying on a rolled-back transaction.
