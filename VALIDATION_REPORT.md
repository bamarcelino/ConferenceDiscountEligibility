# VALIDATION REPORT — Conference Discount Eligibility 1.0.2

## Identification

| Item | Result |
|---|---|
| Report date | 2026-07-17 |
| Plugin | Conference Discount Eligibility 1.0.2 |
| Target Leconfe | 1.4.6, official tag commit `f7e369d` |
| Target Paypal Payment plugin | 1.1.0, official tag commit `6b2a0fc` |
| Application PHP requirement | `^8.1` from the target release; exact production PHP runtime not supplied |
| Validation PHP | PHP CLI 8.4.16 NTS |
| Laravel | Laravel 10 line; exact production lock patch not executable in this container |
| Filament | 3.x target line; 3.3.52 recorded from prior target dependency analysis |
| Livewire | 3.x target line; 3.8.1 recorded from prior target dependency analysis |
| Validation OS | Debian GNU/Linux 13 (trixie), x86_64 |
| Production database | Not supplied |
| Validation database | No PDO driver available beyond base PDO in the build container |
| PayPal Sandbox | **PENDING EXTERNAL CREDENTIALS** |

## Real-target evidence completed before 1.0.2

Conference Discount Eligibility 1.0.1 was uploaded, enabled, and exercised in the real Leconfe 1.4.6 target.

Observed successful results:

- Scheduled Conference navigation and all plugin administration sections rendered.
- Audit Log detail opened after the 1.0.1 fix.
- An exact-user entitlement recalculated one unpaid Participant Payment.
- The payment changed from `EUR 30.00` to `EUR 18.00` for a 40% discount.
- Payment Detail displayed the original fee, `-EUR 12.00`, final total, reason, source, and snapshot timestamp.
- Invoice 003 displayed the original line, a negative eligibility-discount line, and total `EUR 18.00`.
- Audit results reported `matched: 1`, `discounted: 1`, `failed: 0`.

Observed domain-only result:

- The `claec.org` boundary matched correctly.
- The account had no `email_verified_at` value.
- The audit detail recorded `domain #1: email_not_verified` and one rejected unverified-domain candidate.

This is correct behavior for the 1.0.1 security policy and is the production evidence that led to the opt-in author fallback in 1.0.2.

## Scope of 1.0.2

Version 1.0.2 adds:

- a safe default domain policy, `verified_email_only`;
- an explicit `verified_email_or_confirmed_author` alternative;
- same-conference author evidence through submission ownership, linked Author participant, or exact author-list email;
- exclusion of incomplete, declined, payment-declined, and withdrawn submissions;
- evidence fields in evaluated-rule audit/snapshot metadata;
- separate recalculation counts for confirmed-author domain matches;
- an idempotent schema version 2 upgrade;
- PHP 8.1-compatible source syntax.

The 1.0.2 ZIP has not yet been uploaded to the real target. Therefore, the new author fallback is not claimed as production-tested.

## Commands executed

```text
php -d opcache.enable_cli=0 tests/run.php
php -d opcache.enable_cli=0 tests/smoke-entrypoint.php
php -d opcache.enable_cli=0 scripts/lint.php
php -d opcache.enable_cli=0 scripts/secret-scan.php
```

Package validation commands are executed after archive creation:

```text
php -d opcache.enable_cli=0 scripts/validate-package.php ConferenceDiscountEligibility-1.0.2.zip
php -d opcache.enable_cli=0 scripts/validate-package.php ConferenceDiscountEligibility-1.0.2.tar.gz
unzip -t ConferenceDiscountEligibility-1.0.2.zip
unzip -t ConferenceDiscountEligibility-1.0.2-source.zip
tar -tzf ConferenceDiscountEligibility-1.0.2.tar.gz
sha256sum <artifacts>
```

## Automated result

| Suite/check | Executed | Result |
|---|---:|---|
| Standalone unit/source-contract scenarios | Yes | **56 passed, 0 failed, 0 skipped** |
| Entrypoint and PaymentManager signature smoke test | Yes | Passed |
| PHP/Blade syntax lint | Yes | **100 files, 0 failed** |
| Runtime-file credential/secret scan | Yes | Passed |
| PHPUnit/Pest full framework suite | No | Authored tests are included; Composer/full Leconfe vendor tree unavailable |
| Composer audit | No | **NOT RUN** — Composer and package-network access unavailable |
| PHPStan/Psalm | No | **NOT RUN** — tools and complete framework dependency graph unavailable |

The 56 executed scenarios cover the prior payment/discount matrix plus the 1.0.2 additions: secure domain-policy default, confirmed-author opt-in, accepted/rejected submission statuses, all three author-evidence paths, rejection of Author role alone, shared creation/recalculation verifier, auditable evidence, schema v2 upgrade, admin policy controls, confirmed-author recalculation statistics, and PHP 8.1 syntax compatibility.

Machine-readable output is included at `tests/results/standalone.json` in the source package.

## Installation and schema validation

| Check | Status | Evidence/constraint |
|---|---|---|
| 1.0.1 upload and activation in target panel | Executed | Observed in real Leconfe 1.4.6 panel |
| 1.0.1 Participant Payment recalculation | Executed | EUR 30.00 → EUR 18.00 |
| 1.0.1 invoice itemization | Executed | Original EUR 30.00, discount -EUR 12.00, total EUR 18.00 |
| 1.0.1 domain rejection audit | Executed | `email_not_verified` recorded |
| 1.0.2 one-root package layout | Executed after build | Archive extraction/validator |
| 1.0.2 `index.php` and `index.yaml` level | Executed after build | Archive validator |
| 1.0.2 entrypoint | Passed | Stubbed target-signature smoke test |
| 1.0.2 schema v2 logic | Source/test validated | Idempotent `identity_policy` column with safe default |
| 1.0.2 upload/activation in target panel | **NOT YET EXECUTED** | Requires user deployment |
| 1.0.2 confirmed-author domain recalc | **NOT YET EXECUTED** | Requires real target submission/user data |

## Payment and PayPal boundary

The new identity policy changes only eligibility resolution. It does not alter the monetary or gateway boundary already validated with 1.0.1:

- only `TYPE_PARTICIPANT_FEE` is discounted;
- `TYPE_SUBMISSION_FEE` remains unchanged;
- the final amount is stored on the native Payment;
- the official PaypalPayment action continues to consume that Payment amount;
- this plugin never sets `paid_at`, captures PayPal, or calls `fulfillQueued()`.

A PayPal Sandbox transaction using the discounted amount remains **PENDING EXTERNAL CREDENTIALS** and is not reported as passed.

## Security result

Controls retained or added include scheduled-conference isolation, authorization, server-only calculation, integer money arithmetic, boundary-safe domains, a secure verified-email default, explicit confirmed-author opt-in, rejection of self-assigned role-only evidence, same-conference/status-constrained submission evidence, audit metadata, private CSV handling, row locks, paid-payment protection, and no gateway secrets.

Residual risk: exact-email author-list evidence is lower assurance than verified mailbox ownership because author metadata is entered during submission. Administrators should enable the fallback only for trusted institutional domains and reviewed conference submissions.

## Known limitations

1. The new 1.0.2 author fallback has not yet been executed in the production target.
2. PayPal Sandbox remains pending.
3. PaypalPayment 1.1.0 has no open-checkout marker; confirm that no PayPal tab is open before unpaid recalculation.
4. No full framework PHPUnit run, Composer audit, or advanced static analysis was possible in the build container.
5. The exact production PHP patch and database driver were not supplied.
6. Leconfe 1.4.6 has no plugin uninstall callback; plugin data is retained on folder removal.
7. The exact-author-email fallback is an explicitly accepted policy trade-off, not equivalent to verified email.

## Compatibility conclusion

The source and package target **Leconfe 1.4.6 / Paypal Payment 1.1.0 / PHP ^8.1**. No Leconfe core or PaypalPayment file is modified. Version 1.0.1 has real-target evidence for payment recalculation, Payment Detail, invoice, and auditing. Version 1.0.2 is an automated-test-passing upgrade candidate until its new domain-author policy is exercised in the target panel.

## Checksums

Authoritative SHA-256 values are written after packaging to `ConferenceDiscountEligibility-1.0.2.sha256`.
