# Upgrade to 1.3.1

## Purpose

Version 1.3.1 allows authorized administrators to reveal coupon codes again from **Discount Eligibility - Coupon Campaigns** without storing them as plaintext.

It also fixes the HTTP 500 error introduced in 1.3.0 when opening create or edit forms that use the generic Reason selector.

## Install

1. Back up the Leconfe database and plugin directory.
2. Upload `ConferenceDiscountEligibility-1.3.1.zip` through Leconfe 1.4.6 Plugin Management.
3. Confirm version 1.3.1 and open a Scheduled Conference.
4. Open **Coupon Campaigns** and create a temporary campaign.
5. Use **Reveal code** and confirm that the generated value is displayed again.

## Schema upgrade

The idempotent installer upgrades schema version 3 to 4 by adding nullable `conference_discount_coupons.code_encrypted` text storage. Eloquent applies Laravel's authenticated `encrypted` cast before persistence.

Existing hashes, hints, campaigns, limits, redemptions, payments, and audit records are not changed.

## Existing campaigns

Codes created before 1.3.1 were intentionally stored only as one-way keyed hashes and cannot be reconstructed. Those codes remain valid for users.

- If the legacy campaign has no uses, select **Regenerate code** once. The replacement is hashed and encrypted and can subsequently be revealed.
- If the campaign already has uses, preserve it or create a new campaign rather than changing a distributed code unexpectedly.

## Security and rollback

The encrypted payload and hash are hidden from model serialization and excluded from audit logs. Only authorized scheduled-conference administrators can access the reveal action.

Rolling application files back to 1.3.0 leaves the nullable column unused. Do not drop it in production merely to downgrade; restore the database backup for a complete rollback.
