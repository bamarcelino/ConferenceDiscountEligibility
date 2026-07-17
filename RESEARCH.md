# RESEARCH — Conference Discount Eligibility

## Status and scope

Research freeze date: **2026-07-16**  
Target installation confirmed by the owner: **Leconfe 1.4.6** (installed 2026-07-15).  
Official source tag analysed: `leconfe/leconfe:1.4.6`, commit `f7e369d`.  
Official payment plugin confirmed by the owner: **Paypal Payment 1.1.0**.  
Official source tag analysed: `leconfe/PaypalPayment:1.1.0`, commit `6b2a0fc`.

This document records source-level findings before implementation. It intentionally distinguishes verified facts from constraints that could not be validated without the production source tree, runtime output, database credentials, or an authenticated panel session.

## Official sources reviewed

- Leconfe repository and release tag: <https://github.com/leconfe/leconfe/tree/1.4.6>
- Leconfe releases: <https://github.com/leconfe/leconfe/releases/tag/1.4.6>
- Leconfe documentation: <https://leconfe.com/docs/>
- Scheduled Conference: <https://leconfe.com/docs/scheduled-conference/>
- Payment settings: <https://leconfe.com/docs/configuring-payment-settings-in-leconfe-v-1-3-0/>
- Participant registration: <https://leconfe.com/docs/participant-registration-in-leconfe/>
- Presenter registration: <https://leconfe.com/docs/presenter-registration-in-leconfe/>
- PayPal payment plugin documentation: <https://leconfe.com/docs/paypal-payment-plugin/>
- Payment Report documentation: <https://leconfe.com/docs/payment-report/>
- Official PayPal plugin: <https://github.com/leconfe/PaypalPayment/tree/1.1.0>
- PayPal Checkout and Orders v2 documentation: <https://developer.paypal.com/checkout/> and <https://developer.paypal.com/docs/api/orders/v2/>
- PayPal REST and webhooks: <https://developer.paypal.com/api/rest/> and <https://developer.paypal.com/docs/api-basics/notifications/webhooks/>
- Omnipay: <https://github.com/thephpleague/omnipay>
- Omnipay PayPal: <https://github.com/thephpleague/omnipay-paypal>
- Laravel 10 documentation: <https://laravel.com/docs/10.x>
- Filament 3 documentation: <https://filamentphp.com/docs/3.x>
- Livewire 3 documentation: <https://livewire.laravel.com/docs/3.x>
- Composer documentation: <https://getcomposer.org/doc/>
- PHPUnit 10 documentation: <https://docs.phpunit.de/en/10.5/>
- Laravel Metable source: <https://github.com/plank/laravel-metable/tree/5.4.0>

## Target dependency profile

The release `composer.json` targets PHP `^8.1`, Laravel `^10.0`, Filament `^3.1`, League Omnipay `^3`, and PHPUnit `^10.0`. The release dependency graph reports the following locked packages:

| Component | Target finding |
|---|---|
| PHP | Application constraint `^8.1`; exact production PHP runtime was not supplied |
| Laravel | Laravel 10 line; exact production patch was not observable from the provided panel data |
| Filament | `3.3.52` |
| Livewire | `3.8.1` |
| PHPUnit | `10.5.63` |
| `league/omnipay` | `3.2.1` in Leconfe |
| `plank/laravel-metable` | `5.4.0` |
| `akaunting/laravel-money` | `5.2.2` |
| `omnipay/common` | `3.3.0` in PaypalPayment lock |
| `omnipay/paypal` | `3.0.2` in PaypalPayment lock |
| PaypalPayment requirement | `omnipay/paypal:^3.0` |
| Database | Production driver not exposed by the version panel; plugin schema uses Laravel-portable types and is tested structurally against SQLite-compatible SQL abstractions |

## Plugin lifecycle and installer

### Entry point and manifest

A plugin is a folder on the `plugins` disk with:

- `index.php`, which returns an instance of `App\Classes\Plugin`;
- `index.yaml`, parsed by Symfony YAML;
- the folder name matching the manifest `folder` value.

The official PaypalPayment 1.1.0 manifest contains only `name`, `folder`, `author`, `description`, and `version`. Leconfe 1.4.6 does not enforce a manifest field for core-version requirements, so compatibility requirements must be documented and enforced in plugin boot code.

### Installation package

`App\Managers\PluginManager::install()` uses `ZipArchive`, explicitly rejects non-`.zip` uploads, requires one root folder, and validates `index.php` and `index.yaml`. Therefore:

- **the panel-installable artifact must be a ZIP**;
- a `.tar.gz` can be supplied only as an additional distribution artifact;
- renaming a ZIP to `.tar.gz` would be incorrect and is not done.

### Activation and deactivation

The core stores enabled state in `plugin_settings` scoped to the current conference/scheduled conference. `PluginServiceProvider` registers the built-in plugins and calls `Plugin::initialize()`. Enabled plugins are loaded and `bootPlugin()` adds the view namespace then calls plugin `boot()`.

Deactivation prevents boot on the next request. It does not invoke a plugin-specific down/cleanup callback. Uninstall removes the plugin directory; no official pre-uninstall hook is available.

### Plugin migrations

Leconfe 1.4.6 does not automatically discover plugin migrations. In addition, `PluginManager::initialize()` avoids normal plugin initialization for console execution. Consequently an ordinary plugin migration directory is not sufficient by itself.

The implementation uses:

1. a reversible schema definition shared by the packaged migration and runtime installer;
2. an idempotent installer executed during plugin boot;
3. a schema version stored in plugin settings;
4. tables retained on disable/uninstall to avoid silent data loss;
5. an explicit `down()` implementation for controlled maintenance/testing.

## Payment and registration architecture

### Participant registration

`App\Panel\ScheduledConference\Pages\ParticipantRegistration::submit()`:

1. validates that participant registration remains open;
2. creates the `Participant`;
3. loads the selected `PaymentFee`;
4. resolves selected add-ons;
5. calculates total via `PaymentFee::getAmountWithAdditionalItemsFromData()`;
6. calls `PaymentManager::get()->queue()` with total, selected add-ons, and the base amount;
7. optionally creates/sends the invoice notification;
8. redirects to `PaymentDetail`.

No Leconfe hook is called in the form schema or immediately before `queue()`.

### Submission payment

The submission-payment path also calls `PaymentManager::get()->queue()` with `TYPE_SUBMISSION_FEE`, a `PaymentFee`, the authenticated payment user, the selected amount/currency, and the submission model implementing `HasPayment`. Because both flows converge on the same replaceable manager, the plugin applies the same server-side eligibility selection, integer calculation, snapshot, invoice line, PayPal amount, and audit behavior without editing the submission page or core files.

### Presenter registration discrepancy

The documentation contains a “Presenter Registration” guide, but the Leconfe 1.4.6 source contains no modern `PresenterRegistration` page/class and `PaymentManager` exposes only:

- `TYPE_PARTICIPANT_FEE = 1`;
- `TYPE_SUBMISSION_FEE = 2`.

The modern PayPal path operates on `App\Models\Payment`. Legacy `Registration`, `RegistrationType`, and `RegistrationPayment` models still exist, but are not the payment object consumed by PaypalPayment 1.1.0.

The independent integration can apply to both native Leconfe payment types: `TYPE_PARTICIPANT_FEE` and `TYPE_SUBMISSION_FEE`. Presenter categories configured as participant fees are covered by `TYPE_PARTICIPANT_FEE`. A separate presenter-only modern hook cannot be implemented because it does not exist in this target source.

### Payment creation

`App\Managers\PaymentManager` is not final. `PaymentManager::get()` resolves `app(PaymentManager::class)`, making a container binding to a subclass a supported Laravel extension mechanism.

`queue()` creates `App\Models\Payment` and stores:

- `amount` and `currency` on the model;
- `title`, `request_url`, `description`, `additional_items`, and `base_amount` as Metable metadata.

No pre-queue hook exists. The implementation binds `PaymentManager::class` to a compatible subclass and changes only supported participant- or submission-fee queue arguments before calling the parent implementation.

### Payment edits

`PaymentDetail::updatePaymentFeeRecord()` recomputes the standard total, updates the Payment, writes `additional_items`, then writes `base_amount`, and may notify the user. Without additional protection that action would remove a discount.

The plugin observes `App\Models\Meta`; when the `base_amount` key is saved for an unpaid supported payment, it recalculates once with a recursion guard. This re-applies the discount before the native notification block proceeds.

### Invoice and receipt

Payment detail, invoice, and receipt use `base_amount` and `additional_items`. The plugin keeps the original base amount and appends one negative `additional_items` line for the discount. The Payment `amount` is the final payable total. This preserves native rendering without editing views.

### Native Payment Report

No hook was found for adding custom columns to the documented native Payment Report. The plugin therefore:

- preserves the native report unchanged;
- adds a discount section to Payment Detail through `PaymentManager::getPaymentMethodInfolist`;
- provides a scoped “Discount Payment Report” resource with original amount, discount, final amount, reason, eligibility source, user, email, conference, status, payment method, and PayPal payment ID.

## PayPal integration

PaypalPayment 1.1.0 `PaypalPage::handlePayment()` sends:

- `amount => number_format($paymentQueue->amount, 2, '.', '')`;
- `currency => $paymentQueue->currency`;
- the Payment title and return/cancel URLs.

On completion it validates approval, transaction count, amount, and currency, then calls `PaymentManager::fulfillQueued()` and stores `paypal_payment_id`, `paypal_token`, and `paypal_payer_id` metadata.

Therefore the correct integration point is the Leconfe Payment amount. The discount plugin does not configure PayPal, initiate/capture PayPal itself, or mark a payment paid.

PaypalPayment 1.1.0 does not persist an “attempt started” marker before redirect. An unpaid payment can therefore have an active browser-side PayPal checkout that is not detectable by the database. Automatic recalculation defaults to **off** and requires explicit administrative confirmation. Completed payments are never changed.

## Money strategy

Leconfe stores decimal amounts and its PayPal plugin formats two decimal places. The plugin never performs monetary arithmetic with binary floating-point values:

- decimal values are converted to integer minor units;
- percentage is stored as basis points (`4000` = `40.00%`);
- discount multiplication uses integer half-up rounding;
- final decimal values are produced only at the Leconfe boundary;
- EUR is fully supported and covered by tests.

Currencies whose PayPal representation is not two decimal places are rejected for discount application in calculation version 1 rather than silently rounded incorrectly.

## Extension-point map

| Function | Class/file | Hook/extension available | Plugin strategy |
|---|---|---|---|
| Fee selection | `ParticipantRegistration::form()` | No Leconfe hook | Filament panel render hook shows server-rendered eligibility/fee preview; core selection remains unchanged |
| Participant registration submit | `ParticipantRegistration::submit()` | No pre-submit Leconfe hook | Container-resolved PaymentManager subclass intercepts participant-fee queue arguments |
| Submission payment submit | Submission billing flow → `PaymentManager::queue()` | No pre-queue hook | The same manager subclass intercepts `TYPE_SUBMISSION_FEE` and delegates final creation to the parent |
| Presenter payment | No separate 1.4.6 class | None | Presenter categories configured as participant fees are covered |
| Payment calculation | `PaymentManager::queue()` | Container resolution via `PaymentManager::get()` | Compatible subclass computes final amount in minor units then calls parent |
| Payment creation | `PaymentManager::queue()` | Container service replacement | Parent creates native Payment; plugin stores snapshot afterward in same transaction |
| Payment admin edit | `PaymentDetail::updatePaymentFeeRecord()` | No hook | `App\Models\Meta` observer reacts after native `base_amount` write |
| PayPal start | `PaypalPage::handlePayment()` | Uses Payment model amount | No PayPal modification; final Payment amount flows through unchanged |
| PayPal confirmation | `PaypalPage` completion handler | Native `fulfillQueued()` | PayPal remains owner of paid state; a Payment observer only consumes an already-reserved coupon after native `paid_at` changes |
| Payment detail | `PaymentManager::getPaymentMethodInfolist` | Official hook | Add read-only discount section |
| Coupon entry before gateway | `PaymentDetail` → `PaymentManager::getPaymentMethodInfolist` | Official hook | Add a nested Livewire coupon form to unpaid participant and submission payment pages |
| Invoice | native Invoice page/model | Metadata-driven | Preserve base amount and append a negative discount item |
| Receipt | native Receipt page/model | Metadata-driven | Same snapshot/itemization strategy |
| Payment Report | native/documented report | No custom-column hook found | Separate discount report, native report untouched |
| Settings | Plugin settings + own table | Plugin scoped settings | Scheduled-conference-scoped settings page |
| Lifecycle | `PluginManager`, base `Plugin` | `boot`, `onPanel` | Idempotent boot/install; data retained on disable |
| Migrations | No plugin migration discovery | None | Reversible runtime schema installer plus packaged migration |

## Hooks and events verified

Relevant hooks:

- `PaymentManager::getPaymentMethodOptions`
- `PaymentManager::getPaymentMethodActions`
- `PaymentManager::getPaymentMethodInfolist`
- `Payments::PaymentMethodTabs`

Relevant plugin events include install/enable/disable events, but no migration or uninstall-data lifecycle is exposed for third-party tables.

## User verification and domain safety

`App\Models\User` implements Laravel `MustVerifyEmail`, casts `email_verified_at`, and exposes `hasVerifiedEmail()`. Domain eligibility is therefore evaluated only for verified email addresses.

Domain matching is exact or boundary-safe subdomain matching (`candidate === rule` or `str_ends_with(candidate, '.' . rule)`). It does not accept substring matches such as `fakeuniversidade.edu` or suffix extensions such as `universidade.edu.example.com`.

## Independent-plugin conclusion

The monetary path, PayPal integration, snapshots, invoices/receipts, detail section, report, administration, import, audit, and pending-payment recalculation can be implemented as an independent plugin using container binding, model observers, Filament render hooks, and documented Leconfe hooks.

The only functional limitation is the absence of a Leconfe hook inside `ParticipantRegistration::form()`. The plugin supplies a server-rendered page-level preview with every active participant fee, discount, and final base fee. It cannot inject a reactive exact total for an arbitrary add-on selection into the core form without JavaScript or a core hook. The authoritative server calculation and the detailed Payment Detail view are independent and unchanged by this limitation.

No core patch is applied or bundled. A future upstream hook inside `ParticipantRegistration::form()` would improve the reactive add-on preview, but it is not required for the authoritative server-side amount, PayPal hand-off, invoice, receipt, or reporting.

## 1.0.2 — author-confirmed domain identity extension

Production evidence from Leconfe 1.4.6 showed a legitimate `@claec.org` participant payment being rejected by a domain rule because `User::hasVerifiedEmail()` returned false. The rejection was correctly recorded as `email_not_verified`; weakening all domain rules globally would create an impersonation risk.

The target source was rechecked before adding the fallback:

- `App\Models\User::hasVerifiedEmail()` relies on `email_verified_at`.
- `App\Models\Enums\UserRole::selfAssignedRoles()` includes `Author`; therefore the account role alone cannot establish identity.
- `App\Models\Submission` belongs to an owner user, has `participants()`, and its own `isParticipantAuthor()` implementation checks a participant whose related role is `UserRole::Author`.
- `SubmissionParticipant` belongs to both `User` and `Submission` and relates to a role.
- `SubmissionStatus` provides the persisted workflow values used to exclude incomplete, declined, payment-declined, and withdrawn records.

The implemented strategy is an explicit per-domain policy:

| Policy | Evidence accepted | Default |
|---|---|---:|
| `verified_email_only` | matching domain plus verified account email | Yes |
| `verified_email_or_confirmed_author` | verified email, or exact user owns/is an Author participant of a qualifying submission in the same scheduled conference | No |

Version 1.0.2 initially accepted only submission ownership or linked Author-participant evidence. Version 1.0.3 later added exact, normalized submission-author email evidence. The global Author role alone is never used as proof. Existing domain rows remain on the secure default after schema upgrade.

The same `DomainIdentityVerifier` is used during initial Payment creation and explicit unpaid-payment recalculation. The winning rule snapshot records the verification method and author evidence so that invoice/payment changes remain auditable.


## 1.0.3 — exact submission-author email evidence

Leconfe 1.4.6 stores bibliographic authors in the `authors` relation on `Submission`, and the `Author` model provides an email field and email scope. Version 1.0.3 extends the confirmed-author fallback with an exact, normalized, case-insensitive match against `Submission::authors.email` within the same scheduled conference and accepted submission statuses.

The global `Author` account role is not accepted by itself. The official `UserRole` enum includes Author among self-assignable roles, so treating that role as identity proof would allow users to grant themselves an institutional-domain discount.

Evidence sources are now:

- `submission_owner`;
- `submission_participant_author`;
- `submission_author_email`.


## 1.2.0 - coupon campaigns and payment-page redemption

### Feasibility conclusion

Leconfe 1.4.6 does not provide a native coupon model or a coupon field inside participant/submission forms. It does, however, build Payment Detail infolists through `PaymentManager::getPaymentMethodInfolist`, an official hook already used by payment plugins. That hook is sufficient to add a plugin-owned Livewire component after the Payment exists and before the user opens PayPal. No core form or PaypalPayment source edit is required.

### Payment-page lifecycle

1. Participant Registration or Submission Payment creates the native unpaid `Payment`.
2. Payment Detail renders the plugin's `Coupon` section through the official infolist hook.
3. The authenticated payment viewer enters a code.
4. The server normalizes and HMAC-hashes the code, scopes lookup to the current Scheduled Conference, and validates dates, status, payment type, optional Payment Fee restrictions, global limit, and per-user limit.
5. The coupon candidate is compared with direct-user, exact-email, and institutional-domain candidates; discounts do not stack and the highest valid percentage wins.
6. A winning coupon is reserved under a database transaction and pessimistic row locks. The native Payment amount, negative invoice item, snapshot, counters, and audit log are updated atomically.
7. PaypalPayment 1.1.0 continues to read the final native `Payment.amount` and remains solely responsible for checkout and paid-state confirmation.
8. When Leconfe changes `paid_at`, a Payment observer changes the existing reservation to `consumed`. It does not call `fulfillQueued()` or mark the Payment paid.

### Coupon schema

| Table/column | Purpose |
|---|---|
| `conference_discount_coupons` | Scheduled-conference-scoped campaigns, HMAC code hash, masked hint, percentage, validity, limits, payment types, and optional fee restrictions |
| `conference_discount_coupon_redemptions` | One reservation/consumption history row per Payment, with user, campaign, state, timestamps, and non-secret metadata |
| `conference_discount_payment_snapshots.coupon_campaign_id` | Auditable winning coupon reference |
| `conference_discount_settings.coupon_redemption_enabled` | Scheduled-conference setting that enables the payment-page form |

The coupon code itself is not stored. A keyed HMAC-SHA256 digest derived from the normalized code and Laravel `APP_KEY` is stored, together with a masked display hint. Generated codes use 128 random bits. Rotating `APP_KEY` invalidates unresolved coupon lookups and therefore requires replacement of active campaigns.

### Concurrency and limits

Application, replacement, removal, and consumption execute in database transactions. The Payment, coupon campaign, reservation, and snapshot are row-locked where relevant. Reserved and consumed redemptions count toward campaign and per-user limits. The same Payment may re-evaluate its own existing reservation without consuming a second use. A lower second coupon cannot replace a higher coupon already selected.

### Payment safety

Coupon modification uses the same `PaymentSafety::canRecalculate()` policy as administrative recalculation. Paid Payments, Payments with a recorded payment method, and Payments with PayPal completion metadata are immutable. PaypalPayment 1.1.0 does not expose a durable checkout-start marker before redirect, so the UI warns the user not to modify the coupon after opening the gateway and administrators must not recalculate a Payment with an open PayPal tab.

### Administrative lifecycle

`Coupon Campaigns` is a Scheduled Conference resource. Administrators can generate a cryptographically random code or enter a validated custom code, configure payment types and Payment Fees, validity, total and per-user limits, and active state. The full code is displayed only immediately after creation/regeneration. Campaigns with recorded uses cannot be deleted or regenerated through the panel; they can be deactivated.

### 1.2.0 extension map

| Function | Class/file | Extension | Strategy |
|---|---|---|---|
| Payment-page field | `PaymentDetail` | `PaymentManager::getPaymentMethodInfolist` | `ViewEntry` renders plugin Livewire component |
| Coupon validation | `CouponEligibilityService` | Plugin service | Server-only, scheduled-conference-scoped validation |
| Highest-rule selection | `PaymentDiscountService::prepareWithCandidates()` | Existing plugin service | Coupon joins automatic candidates; no stacking |
| Reservation | `CouponRedemptionService::apply()` | Plugin service | Transaction, locks, Payment/snapshot/invoice/audit update |
| Removal | `CouponRedemptionService::remove()` | Plugin service | Releases reservation and restores best automatic rule |
| PayPal hand-off | `PaypalPage::handlePayment()` | Native Payment amount | No PayPal modification |
| Consumption | `PaymentObserver::updated()` | Eloquent observer | Consumes reservation only after native `paid_at` change |
| Administration | `CouponCampaignResource` | Filament resource | Scheduled-conference-scoped CRUD and code generation |

### Independent-plugin conclusion for coupons

The requested payment-stage coupon field is implementable as an independent plugin using the verified Payment Detail infolist hook and a plugin-owned Livewire component. No core patch, textual replacement, PayPal duplication, or manual PHP copy is required. Final browser execution still needs confirmation in the target panel after installing version 1.2.0; local validation cannot substitute for that authenticated Filament/Livewire test.

## 1.2.1 - zero-value payment handling

Leconfe 1.4.6's native `PaymentManager::fulfillQueued()` records `paid_at`, `payment_method`, and `paid_by`. `PaymentDetail` hides gateway actions once `Payment::isPaid()` is true. PaypalPayment 1.1.0 always builds a PayPal purchase from `Payment.amount`, so a zero total must be completed inside Leconfe rather than sent to PayPal.

Participant and Submission payment-required notifications do not inspect `paid_at` before rendering. Version 1.2.1 therefore suppresses those two queued notification classes only when the related Payment has already been completed with `payment_method = full_discount`, then sends Leconfe's native `PaymentConfirmed` notification after commit.

No core patch or PayPal-plugin modification is required. No database migration is required.
