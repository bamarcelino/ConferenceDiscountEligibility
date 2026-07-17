# ARCHITECTURE — Conference Discount Eligibility 1.0.2

## Architectural goals

- Remain independent from Leconfe core files and PaypalPayment.
- Apply a deterministic server-side discount before native Payment creation.
- Preserve the native Payment, PayPal, invoice, receipt, and notification lifecycle.
- Scope every rule and query to the current scheduled conference.
- Preserve an immutable-at-creation snapshot and explicit recalculation history.
- Reject unsafe or ambiguous inputs instead of guessing.

## Component overview

```text
ParticipantRegistration
        |
        v
PaymentManager::get()
        |
        v
DiscountAwarePaymentManager ----> EligibilityResolver
        |                                |
        |                                +-- user entitlement
        |                                +-- exact-email entitlement
        |                                +-- policy-validated institutional domain
        v
DiscountCalculator (integer minor units)
        |
        +-- native Payment.amount = final total
        +-- native meta.base_amount = original base
        +-- native meta.additional_items += negative discount line
        +-- plugin snapshot + audit
        |
        v
PaymentDetail / Invoice / Receipt / PaypalPayment
```

## Core extension mechanisms

### PaymentManager binding

`PaymentManager::get()` resolves from the Laravel container. During plugin boot the plugin binds the core class to `DiscountAwarePaymentManager`, which exactly preserves the target method signature and delegates to `parent::queue()`.

Only `TYPE_PARTICIPANT_FEE` is eligible. `TYPE_SUBMISSION_FEE` is returned unchanged.

### Metadata observer

The core administrative edit path writes Payment fields, then `additional_items`, then `base_amount`. The observer reacts only to a saved `base_amount` metadata record belonging to an unpaid participant Payment. A static recursion guard suppresses plugin-originated metadata writes.

### Filament hooks

- `PanelsRenderHook::PAGE_START`, scoped to `ParticipantRegistration`, renders eligibility and active fee previews.
- `PaymentManager::getPaymentMethodInfolist` adds the read-only discount snapshot to Payment Detail.
- Explicit resources/pages are registered only on the `scheduledConference` panel.

## Data model

### `conference_discount_settings`

One row per scheduled conference. Stores scope, eligible add-on keys, recalculation/notification defaults, and CSV limit.

### `conference_discount_entitlements`

Stores direct-user and exact-email eligibility. The original email is retained for display; normalized email is used for matching. Pending email records can be linked once to a real user ID.

### `conference_discount_domains`

Stores normalized domain, subdomain behavior, validity, status, optional usage limit, and the per-domain identity policy. Schema version 2 defaults existing and new records to `verified_email_only`.

### `conference_discount_payment_snapshots`

One row per Payment. Integer minor-unit columns preserve original base, discount, final base, add-on amount, and final total. JSON metadata preserves evaluated candidates, selected add-on eligibility, and explicit recalculation history.

### `conference_discount_import_batches`

Stores import mode, strategy, counts, source filename, and a structured report. Uploaded files are private and may be deleted after processing according to normal Laravel storage maintenance.

### `conference_discount_audit_logs`

Append-only application log. The plugin UI exposes no edit/delete actions. IP addresses are stored only as an HMAC hash when available.

## Constraints and indexes

- Unique settings row per scheduled conference.
- Unique normalized email per scheduled conference when present.
- Unique normalized domain per scheduled conference.
- Unique snapshot per Payment.
- Foreign keys use cascade for plugin-owned conference/payment rows and null-on-delete for actor/user references where history must remain.
- Candidate and report lookups have composite scheduled-conference indexes.

Nullable unique behavior is backed by application-level transactions and duplicate checks to remain portable across MySQL/MariaDB, PostgreSQL, and SQLite.

## Eligibility resolution

1. Normalize the authenticated user email.
2. Link a pending exact-email entitlement to the user when safe.
3. Evaluate active, currently valid direct-user records.
4. Evaluate active, currently valid exact-email records.
5. Evaluate active, currently valid domain records through their configured identity policy.
6. Under the default policy, require `email_verified_at`. Under the opt-in fallback, accept either verified email or real author evidence in the same scheduled conference.
7. Exclude exhausted usage-limited records.
8. Select the highest basis-point value.
9. On a percentage tie, prefer direct user, exact email, then domain.
10. Record every evaluated candidate, identity decision, author evidence, and the winner.

Rules are non-cumulative.

## Domain identity assurance

The default `verified_email_only` policy remains the strongest available account-ownership check in Leconfe 1.4.6. The optional `verified_email_or_confirmed_author` policy was added for conferences where reliable email verification is unavailable but the user is already represented in the conference submission data.

Confirmed-author evidence is evaluated only inside the same `scheduled_conference_id` and requires one of:

- the user owns a submission (`submissions.user_id`);
- the user is linked through `submission_has_participants` with the Author role;
- the user's exact normalized email appears in the submission `authors` relation.

Only `Queued`, `On Review`, `On Payment`, `On Presentation`, `Editing`, and `Published` submissions count. `Incomplete`, `Declined`, `Payment Declined`, and `Withdrawn` do not. The account-level Author role alone is deliberately ignored because Leconfe exposes it as self-assignable. The selected evidence source and submission identifiers are persisted in evaluated-rule metadata.

## Calculation

`percentage_basis_points` uses hundredths of one percent:

- `4000` = 40.00%
- `3000` = 30.00%
- `10000` = 100.00%

All amounts are converted to minor units before arithmetic. For base-only scope:

```text
eligible = original base
final total = original base + add-ons - round(eligible × percentage)
```

For base-and-eligible-add-ons scope, only add-on keys explicitly configured in settings are added to the eligible amount. Blank key configuration means no add-on is discounted.

The line added to native Payment metadata is negative and marked with `cde_discount_line=true`, allowing safe removal/replacement during recalculation.

## Snapshot and validity

Eligibility validity is checked at Payment creation. The snapshot does not change when a rule later expires or is edited. An unpaid payment changes only through an explicit recalculation or the native fee-edit flow. Paid payments are never recalculated.

Usage limits are consumed only when a new Payment receives a new snapshot. Updating the same snapshot does not consume another use.

## Pending-payment recalculation

The recalculator locks the Payment row and refuses when:

- `paid_at` is set;
- type is not participant fee;
- `payment_method` is already set;
- PayPal completion metadata exists.

PaypalPayment 1.1.0 does not record an initiation marker before redirect. The plugin cannot detect a checkout open in another browser tab, so bulk/automatic recalculation defaults off and shows a warning.

## CSV security

- Private local storage.
- Extension, MIME, byte-size, row-count, encoding, header, email, percentage, and date validation.
- Preview and dry-run execute the same validator as persistence.
- Duplicate detection occurs inside the file and against the current conference.
- Update/ignore/error strategies are explicit.
- Exported cells beginning with `=`, `+`, `-`, or `@` are prefixed with an apostrophe.
- No spreadsheet formulas are evaluated.

## Authorization and tenant isolation

Every page and resource requires the same scheduled-conference update authorization used by Leconfe administration. All resource queries add `scheduled_conference_id = currentScheduledConferenceId`. IDs supplied by the browser are reloaded through scoped queries before modification.

## Installation and schema lifecycle

Plugin boot calls the idempotent installer before registering UI. Schema version 2 adds `conference_discount_domains.identity_policy` with a safe default and leaves all existing rules, payments, snapshots, and audit records intact. Disabling the plugin leaves schema/data intact. Because Leconfe has no uninstall callback, uninstalling the folder also leaves data intact by design. A reversible schema class and migration `down()` method are included for controlled rollback.

## Compatibility boundary

Confirmed design target:

- Leconfe 1.4.6 (`f7e369d`)
- PaypalPayment 1.1.0 (`6b2a0fc`)
- Filament 3.3.52
- Livewire 3.8.1
- PHP `^8.1` application constraint; validation syntax/runtime executed on PHP 8.4.16

The package contains a version guard and refuses to boot on an incompatible Leconfe version when a readable version file reports a value outside `1.4.6`.
