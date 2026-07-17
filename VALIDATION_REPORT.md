# VALIDATION REPORT — Conference Discount Eligibility 1.0.1

## Identification

| Item | Result |
|---|---|
| Report date | 2026-07-17 |
| Plugin | Conference Discount Eligibility 1.0.1 |
| Target Leconfe | 1.4.6, official tag commit `f7e369d` |
| Target Paypal Payment plugin | 1.1.0, official tag commit `6b2a0fc` |
| Application PHP requirement | `^8.1` from the target release; exact production PHP runtime not supplied |
| Validation PHP | PHP CLI 8.4.16 NTS |
| Laravel | Laravel 10 line; exact production/lock patch not independently executable in this container |
| Filament | 3.3.52 from the target release dependency analysis |
| Livewire | 3.8.1 from the target release dependency analysis |
| Validation OS | Debian GNU/Linux 13 (trixie), x86_64 |
| Production database | Not supplied |
| Validation database | No PDO database driver available in the build container |
| PayPal Sandbox | **PENDING EXTERNAL CREDENTIALS** |


## Production evidence that triggered 1.0.1

The 1.0.0 package was uploaded and activated successfully in the target Leconfe 1.4.6 installation. The Scheduled Conference navigation, settings, entitlement/domain resources, audit list, and participant-payment interception were observed in the real panel. A test participant payment produced `discount_not_applied`, confirming the custom `PaymentManager` binding executed. The production test also exposed four operational issues:

1. the direct entitlement shown in the panel belonged to a different account (`brunomarcelino@claec.org`) than the participant payment (`bruno.marcelino@claec.org`);
2. domain recalculation did not alter the payment, but the exact rejection or failure reason was hidden by the 1.0.0 Audit Log detail error;
3. the Audit Log record detail route returned HTTP 500, preventing the administrator from viewing the rejection context;
4. synchronous recalculation displayed a generic completion message even when zero payments were changed.

Version 1.0.1 fixes items 3 and 4, makes items 1 and 2 explicit in the panel, and makes edit-form recalculation toggles operational. The updated package has not yet been uploaded to the target panel, so full runtime verification of the hotfix remains pending.

## Commands executed

```text
php tests/run.php
php tests/smoke-entrypoint.php
php scripts/lint.php
php scripts/secret-scan.php
```

Package validation commands executed:

```text
php scripts/validate-package.php ConferenceDiscountEligibility-1.0.1.zip
php scripts/validate-package.php ConferenceDiscountEligibility-1.0.1.tar.gz
unzip -t ConferenceDiscountEligibility-1.0.1.zip
unzip -t ConferenceDiscountEligibility-1.0.1-source.zip
tar -tzf ConferenceDiscountEligibility-1.0.1.tar.gz
sha256sum <artifacts>
```

Both package-structure validations passed, both ZIP integrity checks passed, the tarball listing succeeded, and extraction confirmed exactly one `ConferenceDiscountEligibility/` root with `index.php` and `index.yaml` at that level. The extracted source package was retested: 48/48 scenarios passed, the entrypoint/signature smoke test passed, 94 files passed lint, and the secret scan passed.

## Automated test result

| Suite/check | Executed | Result |
|---|---:|---|
| Standalone unit/source-contract scenarios | Yes | **48 passed, 0 failed, 0 skipped** |
| Entrypoint and PaymentManager signature smoke test | Yes | Passed |
| PHP/Blade syntax lint | Yes | **94 files, 0 failed** |
| Runtime-file credential/secret scan | Yes | Passed |
| PHPUnit/Pest suite | No | Authored PHPUnit tests are included, but PHPUnit/Composer were unavailable |
| Composer audit | No | **NOT RUN** — Composer and package-network access were unavailable |
| PHPStan/Psalm | No | **NOT RUN** — tools and full framework dependency graph were unavailable |

The 48 executed scenarios cover the requested matrix at the isolated logic/source-contract level: no-discount behavior, 40% and 30%, pending email linking, exact and subdomain matching, malicious similar domains, precedence, inactive/future/expired rules, conference isolation, participant/presenter-category coverage, base/add-on scope, EUR and rounding, zero value, invalid percentages, unpaid recalculation controls, paid-payment protection, CSV validation and duplicates, authorization/source scoping, invoice/receipt itemization contract, Payment Detail/report hooks, PayPal amount delegation, success/cancel responsibility boundaries, duplicate protection, and idempotent schema/snapshot design.

Machine-readable output is included at `tests/results/standalone.json` in the source package.

## Installation and activation validation

| Check | Status | Evidence/constraint |
|---|---|---|
| One root folder in package | Executed after build | Package validator/extraction check |
| `index.php` and `index.yaml` at expected level | Executed after build | Package validator |
| Plugin entrypoint returns plugin instance | Passed | Stubbed target-signature smoke test |
| Upload through authenticated Leconfe panel | **NOT EXECUTED** | No authenticated panel session or staging deployment was supplied |
| Activation in full Leconfe runtime | **NOT EXECUTED** | Complete release/vendor tree unavailable in the build container |
| Runtime schema creation | **NOT EXECUTED against a DB** | Schema was linted/reviewed; no PDO database driver was available |
| Disable/reactivate/update/reinstall | **NOT EXECUTED in panel** | Requires isolated full Leconfe environment |
| Destructive rollback | **NOT EXECUTED against a DB** | Reversible `SchemaDefinition::down()` and migration `down()` are included |

## Registration and payment validation

| Flow | Result |
|---|---|
| Participant-fee calculation | Passed in isolated tests/source-contract checks |
| Presenter registration | Leconfe 1.4.6 exposes no separate modern `PresenterRegistration` class/type; presenter categories charged as participant fees are covered |
| Submission payment | Explicitly excluded and delegated unchanged to core |
| Native Payment final amount | Source contract verifies discount manager passes final amount to `parent::queue()` |
| Native invoice/receipt itemization | Source contract verifies original base plus negative discount additional item |
| Payment Detail | Official infolist hook integration authored and linted; full Filament render not executed |
| Discount Payment Report | Resource/export authored and linted; full Filament render not executed |
| PayPal simulated amount | `EUR 21.00` contract passed for a `EUR 35.00` fee with 40% discount |
| PayPal approved return | Kept entirely in official PaypalPayment; source contract confirms this plugin does not call `fulfillQueued()` or set `paid_at` |
| PayPal cancellation/error/timeout | Responsibility remains official plugin/gateway; this plugin performs no paid-state mutation |
| PayPal Sandbox | **PENDING EXTERNAL CREDENTIALS** |

## Security review result

Implemented controls include scheduled-conference scoping, Leconfe policy authorization, server-only calculation, integer money arithmetic, verified-email domain rules, exact boundary matching, private CSV storage, MIME/size/row/header validation, spreadsheet-formula neutralization on exports, row locks, unique constraints, paid-payment protection, append-only application audit records, HMAC IP hashing, credential redaction, and no PayPal secret handling.

The secret scan found no credential-like material in runtime files.

## Known limitations and residual risks

1. **No claim of full production certification:** the authenticated panel upload, full Laravel/Filament/Livewire boot, real database migrations, and end-to-end Leconfe flows were not executable in this environment.
2. **PayPal Sandbox pending:** no secure Sandbox credentials/environment were supplied. This is not reported as passed.
3. **Open-checkout visibility:** PaypalPayment 1.1.0 does not persist a checkout-start marker before redirect, so an administrator cannot detect a PayPal page open in another browser tab. Unpaid recalculation is explicit and defaults off.
4. **Presenter model discrepancy:** target source has no separate modern presenter-payment type; participant-fee presenter categories are covered, submission fees are not.
5. **Reactive add-on preview:** no official form-schema hook exists inside `ParticipantRegistration`. The plugin shows a server-rendered fee/discount preview; the authoritative total is calculated server-side during Payment creation.
6. **Plugin migration lifecycle:** Leconfe 1.4.6 does not discover plugin migrations and has no pre-uninstall callback. The plugin uses an idempotent boot installer and retains financial data when disabled/uninstalled.
7. **Currency boundary:** calculation version 1 refuses currencies that are not represented with two decimal places because PaypalPayment 1.1.0 formats exactly two decimals.
8. **Production runtime unknown:** exact PHP patch and database driver for the Scientia installation were not supplied.
9. **Composer audit and advanced static analysis:** not executable in the build container; therefore no claim of a clean dependency audit is made.

## Compatibility conclusion

The source and package structure target **Leconfe 1.4.6 / Paypal Payment 1.1.0** and use verified target extension points: Laravel container resolution of `PaymentManager`, Leconfe's Payment infolist hook, Filament panel registration/render hooks, and Eloquent observers. No Leconfe or PaypalPayment file is modified or bundled.

The package is a **staging validation candidate**, not an honestly certified production release, until the remaining panel/database/Sandbox checks above are executed in an isolated clone of the real installation.

## Checksums

The authoritative SHA-256 values are generated after archive creation in the external file `ConferenceDiscountEligibility-1.0.1.sha256`. Embedding an archive's final checksum inside that same archive would change the archive and invalidate the value.
