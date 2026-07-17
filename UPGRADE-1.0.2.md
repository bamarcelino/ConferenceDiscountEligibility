# Upgrade to 1.0.2

Version 1.0.2 is a non-destructive feature and security update for 1.0.1.

## What it adds

- Per-domain identity policy.
- Secure default: `verified_email_only`.
- Explicit opt-in: `verified_email_or_confirmed_author`.
- Confirmed-author evidence based on the exact user owning a submitted work or being linked as an Author participant in the same scheduled conference.
- Rejection of the self-assignable Author role as standalone proof.
- Author-evidence fields in evaluated-rule snapshots, Payment Detail, Audit Log, report details, and CSV report export.
- Recalculation statistic `confirmed_author_domain_matches`.
- Schema version 2 with an idempotent `identity_policy` column.

## Upgrade steps

1. Back up the database.
2. Ensure no PayPal checkout is open for affected unpaid payments.
3. Upload `ConferenceDiscountEligibility-1.0.2.zip` over the existing plugin.
4. Reload the scheduled-conference panel and confirm version 1.0.2.
5. Edit the intended institutional-domain rule.
6. Select **Verified email or confirmed conference author** only for domains where this fallback is approved.
7. Save and recalculate unpaid payments.
8. Verify the Audit Log result and Payment Detail.

Existing domain rules are not weakened automatically; they remain **Verified email only** until explicitly changed.

## Expected test for `claec.org`

For an unverified `@claec.org` user, the domain rule applies only when that exact user owns or is linked as an Author participant to a submitted work in `#Cultures 2027`. The Audit Log should report one confirmed-author domain match. If it reports one identity-rejected match instead, inspect whether the payment user is actually linked to a qualifying submission in that scheduled conference.
