# Upgrade to 1.0.3

## Purpose

Version 1.0.3 extends the opt-in institutional-domain policy `Verified email or confirmed conference author` so that an unverified account can also be confirmed by an exact, case-insensitive email match in the author list of an eligible submission in the same scheduled conference.

The global `Author` role by itself remains insufficient because Leconfe can allow users to self-assign that role.

## Installation

1. Back up the database.
2. Close all open Leconfe tabs.
3. Keep the administrator session in one Chrome profile. Use Incognito or a different Chrome profile for participant/author testing.
4. Disable the plugin temporarily.
5. Upload `ConferenceDiscountEligibility-1.0.3.zip` over the existing plugin.
6. Re-enable the plugin and press `Ctrl+Shift+R`.
7. Confirm version `1.0.3`.

No database migration is required. Existing rules, snapshots, audit logs, invoices, and payments are preserved.

## About “This page has expired”

This message is a Laravel/Livewire 419 response caused by a stale CSRF token. It commonly appears when the same browser profile switches between administrator and participant accounts or keeps tabs open while the session is regenerated. The save/recalculation may already have completed before the stale tab receives the alert.

Close all Leconfe tabs, sign in again, and keep administrator and test-user sessions in separate Chrome profiles.

## Validation test

For an unverified account using an eligible institutional domain, one of the following must exist in the same scheduled conference and in an accepted submission status:

- the account owns the submission;
- the account is linked to the submission as an Author participant;
- the account email exactly matches an email in the submission author list.

Then run `Recalculate unpaid payments`. The audit evidence source should be `submission_owner`, `submission_participant_author`, or `submission_author_email`.
