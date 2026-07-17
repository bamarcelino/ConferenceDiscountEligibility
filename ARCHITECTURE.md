# ARCHITECTURE - Conference Discount Eligibility 1.2.0

## Architectural goals

- Remain independent from Leconfe core files and PaypalPayment.
- Apply every amount change on the server before the payment gateway is opened.
- Preserve the native Payment, PayPal, invoice, receipt, report, and notification lifecycle.
- Scope rules, coupons, reservations, and queries to the current scheduled conference.
- Use integer minor units and basis points for monetary calculations.
- Preserve auditable snapshots and explicit recalculation history.
- Prevent completed or already initiated payments from being repriced.

## Component overview

```text
ParticipantRegistration / Submission payment creation
        |
        v
PaymentManager::get()
        |
        v
DiscountAwarePaymentManager ----> EligibilityResolver
        |                                +-- direct user
        |                                +-- exact email
        |                                +-- institutional domain
        v
native Payment created with final automatic amount
        |
        +------------------------+
        |                        |
        v                        v
Payment Detail             Coupon Campaigns
official infolist hook      scheduled-conference admin
        |                        |
        v                        v
Livewire Coupon form ---> CouponRedemptionService
                                  |
                                  +-- normalize and keyed-hash code
                                  +-- validate campaign/payment/user limits
                                  +-- compare automatic rules + coupons
                                  +-- lock Payment/campaign/redemption rows
                                  +-- update Payment.amount
                                  +-- replace negative discount line
                                  +-- update snapshot and audit
                                  +-- reserve winning coupon
                                          |
                                          v
                               official PaypalPayment 1.1.0
                                          |
                                          v
                               PaymentManager::fulfillQueued()
                                          |
                                          v
                                 paid_at update observer
                                          |
                                          v
                                   consume reservation
```

## Core extension mechanisms

### PaymentManager binding

`PaymentManager::get()` resolves from the Laravel container. During plugin boot, the plugin binds the core manager to `DiscountAwarePaymentManager`, preserves the target `queue()` signature, calculates automatic eligibility, and delegates payment creation to `parent::queue()`.

Both native Leconfe 1.4.6 types are supported:

- `TYPE_PARTICIPANT_FEE`;
- `TYPE_SUBMISSION_FEE`.

Unknown future payment types are delegated unchanged.

The plugin does not override `fulfillQueued()` and does not set `paid_at`. The official payment mechanism remains solely responsible for completing payments.

### Payment-detail infolist hook

Leconfe 1.4.6 calls `PaymentManager::get()->getPaymentMethodInfolist()` in the right-hand Payment Detail column. The plugin registers two sections through the official `PaymentManager::getPaymentMethodInfolist` hook:

- read-only discount snapshot details;
- a `ViewEntry` hosting the nested Livewire coupon component.

No core Blade template or Payment Detail class is replaced.

### Livewire coupon component

`CouponRedemption` receives only a Payment ID. Every mount, render, apply, and remove request reloads the Payment and verifies:

- current scheduled conference matches the Payment;
- authenticated user can view the Payment;
- the payment remains safely recalculable.

The browser submits only the coupon string. Percentage, reason, amount, eligibility, and limits are resolved on the server. Invalid attempts are rate-limited by user, payment, and hashed IP context.

### Payment observer

The official PayPal plugin calls the native `PaymentManager::fulfillQueued()`, which updates `paid_at`. The plugin observes that native update and changes a reserved coupon to consumed. The observer is idempotent and does not modify the Payment.

### Metadata observer

The native administrative fee-edit path writes Payment fields and then `base_amount`. The existing metadata observer reapplies the stored snapshot only to a safely unpaid supported Payment. A recursion guard suppresses plugin-originated metadata writes.

## Data model

### `conference_discount_settings`

One row per scheduled conference. Stores discount scope, eligible add-on keys, recalculation defaults, CSV size limit, schema version, and `coupon_redemption_enabled`.

### `conference_discount_entitlements`

Stores direct-user and exact-email rules. Original email is retained for display; normalized email is used for matching. Pending email records may later link to one real user ID.

### `conference_discount_domains`

Stores normalized domain, subdomain behavior, identity policy, percentage, validity, status, and optional usage limit.

### `conference_discount_coupons`

Stores one campaign/code per row:

- scheduled conference;
- campaign name;
- keyed code hash and masked hint;
- percentage and reason;
- eligible native payment types;
- optional eligible Payment Fee IDs;
- validity and active status;
- optional global maximum uses;
- per-user limit;
- current reserved/consumed use count;
- creator/updater and timestamps.

The full code is never persisted.

### `conference_discount_coupon_redemptions`

One row per Payment. The unique `payment_id` constraint prevents two active coupon records for the same payment. Statuses are:

- `reserved`;
- `consumed`;
- `released`;
- `revoked`.

The record stores campaign, user, timestamps, and non-secret context.

### `conference_discount_payment_snapshots`

One row per Payment. Integer minor-unit columns preserve original base, discount, final base, add-ons, original total, and final total. The row can reference a user/email entitlement, domain rule, or coupon campaign. JSON metadata preserves the native payment type, evaluated candidates, identity evidence, scope, add-on configuration, origin, and recalculation history.

### Import and audit tables

Import batches retain validation/report metadata. Audit logs retain actor, affected user, action, safe before/after data, context, origin, time, and one-way IP hash. Coupon hashes and full codes are excluded.

## Automatic eligibility

The resolver evaluates:

1. direct `user_id` rule;
2. exact normalized email rule;
3. boundary-safe institutional-domain rule.

A domain rule may require verified email or explicitly allow verified email/confirmed same-conference authorship. Confirmed authorship requires concrete submission evidence: owner, linked participant with Author role, or exact normalized email in the submission author list. The global self-assignable Author role alone is not sufficient.

## Coupon eligibility

A submitted code is normalized and HMAC-SHA256 hashed with the Laravel application key. Lookup includes `scheduled_conference_id`, so an identical code may be independently configured in another conference.

The campaign is rejected when any condition fails:

- missing/inactive/not-yet-valid/expired;
- global maximum uses reached;
- wrong native payment type;
- Payment Fee not allowed;
- per-user limit reached;
- different scheduled conference;
- payment no longer safely recalculable.

A campaign row, Payment row, relevant redemption rows, and usage rows are locked inside a database transaction.

## Selection and precedence

Discounts are non-cumulative. The highest percentage wins. Equal percentages use this stable priority:

1. direct user;
2. coupon;
3. exact email;
4. institutional domain.

When replacing a coupon, the existing reserved coupon is also included in selection. A lower new coupon therefore cannot downgrade an already reserved higher coupon. A valid but losing coupon is not reserved or consumed.

## Calculation

Percentages use basis points:

- `4000` = 40.00%;
- `3000` = 30.00%;
- `10000` = 100.00%.

All arithmetic uses integer minor units. The default scope discounts only the base fee. Optional eligible add-ons are included only when their exact generated keys are configured.

The native Payment receives:

- final amount in `Payment.amount`;
- original base in `meta.base_amount`;
- original add-ons plus one negative `cde_discount_line` in `meta.additional_items`.

Invoice, receipt, Payment Detail, reports, and PaypalPayment therefore consume the same native Payment value and itemization.

## Coupon lifecycle

### Apply

1. Lock Payment and campaign.
2. Validate campaign and user/payment limits.
3. Evaluate automatic rules, submitted coupon, and any current reserved coupon.
4. If submitted coupon wins, update amount, line item, snapshot, use counters, audit, and reservation.
5. If another rule wins, leave current payment/reservation unchanged.

### Remove

Removal is allowed only before payment activity. The plugin releases the reservation and recalculates the best remaining automatic rule.

### Consume

When native `paid_at` changes from null to a timestamp, the Payment observer converts `reserved` to `consumed`. Repeated observer/service calls are no-ops after the first transition.

### Release

Replacement, explicit removal, or administrative recalculation that changes the winner releases the coupon and decrements its snapshot use count.

## PayPal boundary

PaypalPayment 1.1.0 reads `Payment.amount`, sends that amount and currency to PayPal, validates the returned amount/currency, then calls native `fulfillQueued()`. The discount plugin supplies the final Payment value but does not create PayPal orders, credentials, redirects, return handlers, or completion logic.

Because PaypalPayment 1.1.0 does not store an open-checkout marker before redirect, the plugin blocks changes when `payment_method` or PayPal completion metadata exists but cannot detect a PayPal tab that was opened and not yet returned. Users and administrators must not apply/remove/recalculate discounts after opening PayPal in another tab.

## Authorization and tenant isolation

Administrative resources require the scheduled-conference update authorization used by the plugin. Resource queries are scoped to current `scheduled_conference_id`. Coupon form requests reload the Payment and enforce the native view policy. Payment Fee IDs selected for a campaign are validated to belong to the same conference.

## Schema lifecycle

Schema version 3 is installed idempotently under a cache lock. It creates coupon tables and adds missing coupon columns to existing settings and snapshots. Foreign keys, unique constraints, lookup indexes, and a reverse-order `down()` are provided.

Disabling the plugin leaves schema and data intact. A production downgrade should restore a database backup rather than dropping coupon structures beneath existing coupon snapshots.

## Compatibility boundary

Confirmed design target:

- Leconfe 1.4.6 (`f7e369d`);
- PaypalPayment 1.1.0 (`6b2a0fc`);
- Laravel/Filament/Livewire versions bundled by that Leconfe release;
- PHP application constraint `^8.1`.

Local validation was executed on PHP 8.4.16. Full target-panel and PayPal Sandbox validation are recorded separately in `VALIDATION_REPORT.md`.
