# Configuration

## Individual Entitlements

Select an existing user and configure percentage, reason, validity, status, notes, and optional maximum uses. The entitlement is linked to the exact `users.id`.

## Email Lists

Enter an exact email address. Matching is trim-normalized and case-insensitive. The original address remains available for display. When a matching user exists or later uses that account, the record is linked to the user.

## Institutional Domains

Enter only the domain, for example `universidade.edu`. Matching is boundary-safe:

- `user@universidade.edu` matches;
- `user@fakeuniversidade.edu` does not;
- `user@universidade.edu.example.com` does not.

Enable subdomains only when intended.

### Domain identity verification

Each domain rule has one of two policies.

#### Verified email only

Default and strongest option. The user's exact email domain must match and `email_verified_at` must be present in Leconfe.

Existing domain rules remain on this policy after upgrade to 1.0.3.

#### Verified email or confirmed conference author

Explicit fallback for installations where author accounts may not have completed email verification. An unverified user is accepted only when that exact user is:

- the owner of a submitted work in the current scheduled conference; or
- a linked submission participant with role `Author` in the current scheduled conference.

Accepted submission statuses are `Queued`, `On Review`, `On Payment`, `On Presentation`, `Editing`, and `Published`. `Incomplete`, `Payment Declined`, `Declined`, and `Withdrawn` do not establish author evidence.

The self-assignable Author role alone is not accepted as identity proof. An exact normalized email in the submission author list is accepted because it is concrete submission-scoped evidence.

## Suggested presets

- 40% — CLAEC active member / Institutional partner affiliate / Research4Life Group A
- 30% — Research4Life Group B / Individual approval

Any percentage from 0.01% through 100.00% may be configured.

## Scope

Default: **Base fee only**. This applies to both participant-registration and submission fees.

Optional: **Base fee and eligible add-ons**. This affects add-ons only; participant and submission base fees are always evaluated. Enter one generated add-on key per line. Blank keys mean no add-on discount.

## CSV

Required columns:

```text
email,discount_percentage,reason,valid_from,valid_until,notes
```

Use Preview first, then Dry Run. Duplicate strategies are `ignore`, `update`, and `error`. Dates use ISO `YYYY-MM-DD` or ISO date-time values.

## Recalculation

Completed payments are never changed. Unpaid recalculation is explicit and defaults off because PaypalPayment 1.1.0 does not persist a checkout-start marker before redirect. Confirm no PayPal checkout is open before recalculating.

The result reports matched, discounted, unchanged, skipped, paid, failed, identity-rejected domain matches, and matches accepted through confirmed authorship. Candidate searches include unpaid participant and submission payments.
