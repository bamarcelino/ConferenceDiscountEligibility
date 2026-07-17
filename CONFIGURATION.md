# Configuration

## Rules

### Individual Entitlements

Select an existing user and configure percentage, reason, validity, status, notes, and optional maximum uses.

### Email Lists

Enter an exact email address. Matching is trim-normalized and case-insensitive. The original address remains available for display. When a matching user exists or later uses the account, the record is linked to the user.

### Institutional Domains

Enter only the domain, for example `universidade.edu`. Domain rules require a verified Leconfe email. Enable subdomains only when intended. Matching is boundary-safe.

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
