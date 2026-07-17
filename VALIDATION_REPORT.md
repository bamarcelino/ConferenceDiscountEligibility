# VALIDATION REPORT — Conference Discount Eligibility 1.0.3

## Identification

| Item | Result |
|---|---|
| Report date | 2026-07-17 |
| Plugin | Conference Discount Eligibility 1.0.3 |
| Target Leconfe | 1.4.6, official tag commit `f7e369d` |
| Target Paypal Payment plugin | 1.1.0, official tag commit `6b2a0fc` |
| Validation PHP | PHP CLI 8.4.16 NTS |
| Laravel | Laravel 10 line used by target Leconfe |
| Filament | 3.3.52 from target dependency analysis |
| Livewire | 3.8.1 from target dependency analysis |
| Validation OS | Debian GNU/Linux 13, x86_64 |
| PayPal Sandbox | PENDING EXTERNAL CREDENTIALS |

## Real-installation evidence

The plugin has been installed in the real Leconfe 1.4.6 panel. Version 1.0.1 successfully recalculated an unpaid participant payment from EUR 30.00 to EUR 18.00, updated Payment Detail, produced an audit snapshot, and regenerated Invoice 003 with the negative discount line.

Version 1.0.2 was uploaded and its institutional-domain policy was saved in the real panel. The observed `This page has expired` alert is consistent with a stale Laravel/Livewire CSRF token after switching accounts or retaining tabs across a regenerated browser session; the save and recalculation had already completed, as confirmed by the updated policy badge and audit record.

Version 1.0.3 adds exact author-list email evidence. This new path has not yet been executed in the target panel.

## Commands executed

```text
php tests/run.php
php tests/smoke-entrypoint.php
php scripts/lint.php
php scripts/secret-scan.php
php scripts/validate-package.php ConferenceDiscountEligibility-1.0.3.zip
unzip -t ConferenceDiscountEligibility-1.0.3.zip
unzip -t ConferenceDiscountEligibility-1.0.3-source.zip
sha256sum ConferenceDiscountEligibility-1.0.3.zip
```

## Automated results

| Check | Result |
|---|---|
| Standalone unit/source-contract scenarios | 61 passed, 0 failed |
| Entrypoint and PaymentManager signature smoke test | Passed |
| PHP/Blade syntax lint | 103 files, 0 failed |
| Runtime secret scan | Passed |
| Package root and manifest validation | Passed |
| PHPUnit/Pest with full Laravel runtime | Not executed in this container |
| Composer audit | Not executed - Composer/package network unavailable |
| PHPStan/Psalm | Not executed - full dependency graph unavailable |

## 1.0.3-specific checks

- Exact normalized email match against the submission author list is implemented.
- The query is scoped to the same scheduled conference.
- Only accepted submission statuses are considered.
- Existing owner and linked participant/Author evidence remains active.
- The global self-assignable Author role alone remains rejected.
- No database migration is required.
- Existing rules, payments, snapshots, invoices, and audit records are preserved.

## Security review

The author fallback requires concrete submission evidence. Email matching uses `LOWER(TRIM(email)) = ?` with a bound parameter and normalized input. The plugin does not trust a user-controlled percentage, global account role, browser amount, or PayPal credential.

## Known limitations

1. The 1.0.3 author-list email path is not yet validated in the live Scientia installation.
2. PayPal Sandbox remains pending.
3. A stale Livewire tab may receive HTTP 419 after the browser session is regenerated. Administrators and participant test accounts should use separate Chrome profiles or Incognito.
4. Composer audit and full-framework PHPUnit/static analysis were not available in the build container.

## Compatibility conclusion

The package targets Leconfe 1.4.6 and PaypalPayment 1.1.0. It modifies neither core nor the official PayPal plugin. Version 1.0.3 is a staging/production-update candidate for validating the exact author-list email evidence path.
