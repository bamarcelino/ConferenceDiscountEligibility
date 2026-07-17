# Upgrade to 1.0.1

This is a non-destructive code-only hotfix for Conference Discount Eligibility 1.0.0.

## What it fixes

- Audit Log detail records no longer render JSON arrays through the failing state formatter.
- Recalculation displays the actual result instead of a generic success message.
- Recalculation failures are reported to the Laravel log and represented in the audit trail.
- Domain matches rejected because `email_verified_at` is empty are counted explicitly.
- The individual-entitlement form warns that the selected `users.id` is authoritative.

## Upgrade

1. Back up the database.
2. In the Scheduled Conference plugin manager, upload `ConferenceDiscountEligibility-1.0.1.zip` using the same Chrome MIME helper if the Leconfe upload field rejects the browser-provided ZIP MIME type.
3. Reload the panel after the upload completes.
4. Confirm the plugin version is 1.0.1 and remains enabled.
5. Open Audit Log. The list now has a Result column, and View should open without HTTP 500.

There are no schema changes. Existing settings, rules, snapshots, and audit logs are retained.

## Correcting the current test payment

The participant payment belongs to `bruno.marcelino@claec.org`. The previously displayed individual entitlement belonged to the different account `brunomarcelino@claec.org`.

Create one of the following for the exact dotted address/account:

- Individual Entitlement: select the option displaying `bruno.marcelino@claec.org` and verify the shown user ID; or
- Email Lists: add `bruno.marcelino@claec.org` exactly.

Keep **Recalculate eligible unpaid payments** enabled. A successful correction should report at least:

- Matched: 1
- Discounted: 1
- Failed: 0

For a EUR 30.00 fee and 40% discount, Payment #3 should become EUR 18.00, and Invoice 003 should show a EUR -12.00 discount line.

A domain rule for `claec.org` remains intentionally ineligible while the user email is not verified. In 1.0.1, the notification reports that condition as an unverified-domain match instead of silently reporting completion.
