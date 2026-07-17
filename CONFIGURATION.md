# Configuration

## Rules

### Individual Entitlements

Select an existing user and configure percentage, reason, validity, status, notes, and optional maximum uses. The entitlement is bound to the exact `users.id`.

### Email Lists

Enter an exact email address. Matching is trim-normalized and case-insensitive. The original address remains available for display. When a matching user exists or later uses the account, the record is linked to the user.

### Institutional Domains

Enter only the domain, for example `universidade.edu`. Enable subdomains only when intended. Matching is boundary-safe: `user@universidade.edu` can match, while `user@fakeuniversidade.edu` and `user@universidade.edu.example.com` cannot.

Each domain has an identity policy:

1. **Verified email only (recommended)** — the Leconfe account must have `email_verified_at`.
2. **Verified email or confirmed conference author** — verified accounts are accepted normally; an unverified account is accepted only when author evidence exists in the same scheduled conference.

Confirmed-author evidence is one of:

- the account owns a submitted work;
- the account is linked as an Author participant on a submitted work;
- the account's exact normalized email appears in the submitted work's author list.

Only submitted, non-negative statuses are accepted: `Queued`, `On Review`, `On Payment`, `On Presentation`, `Editing`, and `Published`. Draft (`Incomplete`), declined, payment-declined, and withdrawn submissions are excluded.

The Leconfe `Author` account role is self-assignable and therefore is not trusted by itself. The confirmed-author fallback is weaker than verified-email ownership and must be enabled deliberately per institutional domain.

Existing domain rules upgraded from 1.0.1 remain on **Verified email only** until edited.

## Suggested presets

- 40% — CLAEC active member / Institutional partner affiliate / Research4Life Group A
- 30% — Research4Life Group B / Individual approval

Any percentage from 0.01% through 100.00% may be configured.

## Scope

Default: **Base registration fee only**.

Optional: **Base fee and eligible add-ons**. Enter one generated add-on key per line. Add-on keys are visible in Payment Fee metadata and begin with `addon_`. Blank keys mean no add-on discount.

## CSV

Required columns:

```text
email,discount_percentage,reason,valid_from,valid_until,notes
```

Use Preview first, then Dry Run. Choose duplicate strategy:

- `ignore`: keep the existing conference record;
- `update`: update the existing exact-email rule;
- `error`: reject duplicates.

Dates use ISO `YYYY-MM-DD` or an ISO date-time. Percentages use decimal percent values such as `40` or `30.5`.

## Recalculation

Completed payments are never changed. Unpaid recalculation is explicit and defaults off because PaypalPayment 1.1.0 does not persist a checkout-start marker before redirect. Confirm no PayPal checkout is open for the affected payment before recalculating.

For a domain recalc, the result reports:

- domain-matching candidates;
- identity-accepted matches;
- discounted, unchanged, skipped, paid, and failed payments;
- matches rejected because neither verified email nor the selected author fallback was satisfied;
- matches accepted through confirmed-author evidence.
