# Upgrade to 1.0.1

This is a non-destructive code-only hotfix for Conference Discount Eligibility 1.0.0.

## What it fixes

- Audit Log detail records no longer pass array/JSON payloads directly to the failing Filament text-state renderer.
- Recalculation displays the actual result instead of a generic completion message.
- Recalculation failures are sent to the Laravel log and represented in the audit trail instead of being swallowed silently.
- Domain matches rejected because `email_verified_at` is empty are counted explicitly.
- Recalculation toggles on edit forms now execute.
- User selection shows the exact account email and numeric user ID.

## Upgrade

1. Back up the database.
2. Do not start PayPal for the unpaid payment that will be recalculated.
3. In the Scheduled Conference plugin manager, upload `ConferenceDiscountEligibility-1.0.1.zip` over the existing plugin. Use the same Chrome MIME helper only if Leconfe rejects the browser-provided ZIP MIME type.
4. Reload the panel. If the old version is still displayed, disable and enable the plugin once, then reload again.
5. Confirm the plugin version is 1.0.1.
6. Open Audit Log. The list now contains a Result column, and View should open without HTTP 500.

There are no schema changes. Existing settings, rules, snapshots, payments, and audit records are retained.

## Correcting Participant Payment #3

The participant payment belongs to `bruno.marcelino@claec.org`. The individual entitlement previously shown belonged to the different account `brunomarcelino@claec.org`.

Create an Individual Entitlement for the exact dotted address/account. In the search result, verify the displayed user ID before saving. Keep **Recalculate eligible unpaid payments** enabled.

For a EUR 30.00 fee and a 40% discount, a successful notification should report at least:

- matched: 1;
- discounted: 1;
- failed: 0.

Payment #3 should become EUR 18.00 and Invoice 003 should show a EUR -12.00 discount line.

A `claec.org` domain rule applies only when the account email is verified. Version 1.0.1 reports an unverified-domain match explicitly; it does not weaken this security rule.
