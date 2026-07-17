# Security Review

## Implemented controls

- Scheduled-conference scoping on every administrative query.
- Leconfe scheduled-conference update authorization on every page/resource/action.
- Server-only eligibility and calculation.
- Integer minor-unit monetary arithmetic and bounded basis points.
- Exact email normalization and boundary-safe domain matching.
- Verified-email requirement for domain rules.
- Eloquent guarded/fillable fields and server-assigned conference/actor IDs.
- Database transactions, row locks, unique snapshot/domain/email constraints, and recursion guards.
- Paid-payment and PayPal-completion checks before recalculation.
- Private CSV storage, MIME/extension/size/row validation, no formula execution, and safe exports.
- Append-only audit UI and HMAC-hashed IP when available.
- No PayPal credentials, card data, tokens, or production personal data in the package.
- No core or PaypalPayment file replacement.

## Threat review

| Threat | Control |
|---|---|
| IDOR / cross-conference access | scoped queries plus authorization |
| Privilege escalation | `can('update', current scheduled conference)` gate |
| CSRF | Filament/Livewire authenticated form actions |
| SQL injection | Eloquent/query builder, no user-built SQL |
| XSS | escaped Blade/Filament output; reason/notes are plain text |
| Mass assignment | explicit fillable plus server mutation |
| Domain spoofing | exact/boundary comparison and verified email |
| Frontend value tampering | browser values are ignored by PaymentManager calculation |
| Duplicate payment/recalculation | unique snapshot, transaction locks, paid checks |
| CSV abuse | private storage, validation, limit, safe export |
| Secret disclosure | no gateway secrets read or logged |
| Audit tampering | no UI edit/delete actions; database access remains a privileged operational boundary |

## Residual risks

- PaypalPayment 1.1.0 stores no checkout-start marker. An administrator can recalculate an unpaid payment while a PayPal checkout is open elsewhere. Recalculation is therefore explicit and warned.
- Leconfe 1.4.6 has no plugin uninstall callback; data is retained after folder removal.
- The production database driver and exact PHP runtime were not supplied.
- Composer audit and a real panel/Sandbox execution require an environment with the complete release/vendor tree and secure Sandbox credentials.

## Dependency audit status

The source contains no third-party runtime dependency beyond packages already provided by Leconfe 1.4.6. `composer audit` was not executable in the build container because Composer and external package-network access were unavailable. This is reported as **NOT RUN**, not as passed.
