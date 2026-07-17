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

### Presenter registration discrepancy

The documentation contains a “Presenter Registration” guide, but the Leconfe 1.4.6 source contains no modern `PresenterRegistration` page/class and `PaymentManager` exposes only:

- `TYPE_PARTICIPANT_FEE = 1`;
- `TYPE_SUBMISSION_FEE = 2`.

The modern PayPal path operates on `App\Models\Payment`. Legacy `Registration`, `RegistrationType`, and `RegistrationPayment` models still exist, but are not the payment object consumed by PaypalPayment 1.1.0.

The independent integration therefore applies to all `TYPE_PARTICIPANT_FEE` payments, including presenter categories configured as participant fees. It deliberately does not alter `TYPE_SUBMISSION_FEE`. A separate presenter-only modern hook cannot be implemented because it does not exist in this target source.

### Payment creation

`App\Managers\PaymentManager` is not final. `PaymentManager::get()` resolves `app(PaymentManager::class)`, making a container binding to a subclass a supported Laravel extension mechanism.

`queue()` creates `App\Models\Payment` and stores:

- `amount` and `currency` on the model;
- `title`, `request_url`, `description`, `additional_items`, and `base_amount` as Metable metadata.

No pre-queue hook exists. The implementation binds `PaymentManager::class` to a compatible subclass and changes only participant-fee queue arguments before calling the parent implementation.

### Payment edits

`PaymentDetail::updatePaymentFeeRecord()` recomputes the standard total, updates the Payment, writes `additional_items`, then writes `base_amount`, and may notify the user. Without additional protection that action would remove a discount.

The plugin observes `App\Models\Meta`; when the `base_amount` key is saved for an unpaid participant payment, it recalculates once with a recursion guard. This re-applies the discount before the native notification block proceeds.

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
| Registration submit | `ParticipantRegistration::submit()` | No pre-submit Leconfe hook | Container-resolved PaymentManager subclass intercepts queue arguments |
| Presenter payment | No separate 1.4.6 class | None | Participant-fee categories are covered; submission fees are excluded |
| Payment calculation | `PaymentManager::queue()` | Container resolution via `PaymentManager::get()` | Compatible subclass computes final amount in minor units then calls parent |
| Payment creation | `PaymentManager::queue()` | Container service replacement | Parent creates native Payment; plugin stores snapshot afterward in same transaction |
| Payment admin edit | `PaymentDetail::updatePaymentFeeRecord()` | No hook | `App\Models\Meta` observer reacts after native `base_amount` write |
| PayPal start | `PaypalPage::handlePayment()` | Uses Payment model amount | No PayPal modification; final Payment amount flows through unchanged |
| PayPal confirmation | `PaypalPage` completion handler | Native `fulfillQueued()` | No plugin intervention; PayPal remains owner of paid state |
| Payment detail | `PaymentManager::getPaymentMethodInfolist` | Official hook | Add read-only discount section |
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

## User verification, author evidence, and domain safety

`App\Models\User` casts `email_verified_at` and exposes `hasVerifiedEmail()`, which returns true only when that timestamp is present. Version 1.0.0/1.0.1 therefore required verified email for every institutional-domain rule. Live testing confirmed the behavior: the domain boundary matched `claec.org`, but the evaluation returned `email_not_verified`.

The Leconfe 1.4.6 source also shows that the account-level `Author` role is included in `UserRole::selfAssignedRoles()`. It cannot be used by itself as identity proof. Actual conference authorship is represented by stronger, scheduled-conference-scoped records:

- `Submission::user()` / `submissions.user_id` identifies the submission owner; Leconfe's `Submission::isAuthor()` treats that owner as the author.
- `Submission::participants()` and `Submission::isParticipantAuthor()` identify a user linked to the submission with the Author role.
- `Submission::authors()` exposes `Author` records, and `Author` stores an email and belongs to a submission.

Submission statuses are backed by `SubmissionStatus`. The accepted author-evidence set is `Queued`, `On Review`, `On Payment`, `On Presentation`, `Editing`, and `Published`. `Incomplete`, `Payment Declined`, `Declined`, and `Withdrawn` are excluded.

Version 1.0.2 therefore adds a per-domain policy rather than weakening all domain rules globally:

- `verified_email_only` remains the default;
- `verified_email_or_confirmed_author` accepts an unverified account only when one of the real submission links above exists in the same scheduled conference.

The exact-email author-list path is lower assurance than mailbox verification because author metadata is entered during submission. It is retained only as an explicit, audited policy option for trusted institutional domains. The account's self-assigned Author role alone is never accepted.

Domain matching remains exact or boundary-safe subdomain matching (`candidate === rule` or `str_ends_with(candidate, '.' . rule)`). It does not accept substring matches such as `fakeuniversidade.edu` or suffix extensions such as `universidade.edu.example.com`.

## Independent-plugin conclusion

The monetary path, PayPal integration, snapshots, invoices/receipts, detail section, report, administration, import, audit, and pending-payment recalculation can be implemented as an independent plugin using container binding, model observers, Filament render hooks, and documented Leconfe hooks.

The only functional limitation is the absence of a Leconfe hook inside `ParticipantRegistration::form()`. The plugin supplies a server-rendered page-level preview with every active participant fee, discount, and final base fee. It cannot inject a reactive exact total for an arbitrary add-on selection into the core form without JavaScript or a core hook. The authoritative server calculation and the detailed Payment Detail view are independent and unchanged by this limitation.

No core patch is applied or bundled. A future upstream hook inside `ParticipantRegistration::form()` would improve the reactive add-on preview, but it is not required for the authoritative server-side amount, PayPal hand-off, invoice, receipt, or reporting.
