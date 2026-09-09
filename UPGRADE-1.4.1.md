# Upgrade to 1.4.1

Version 1.4.1 adds explicit compatibility with Leconfe 1.5.1 while preserving support for Leconfe 1.5.0 and 1.4.6.

## Root cause of the Leconfe 1.5.1 HTTP 500

Conference Discount Eligibility 1.4.0 used a strict Leconfe version allow-list containing only `1.4.6` and `1.5.0`. During Scheduled Conference boot, the compatibility guard rejected `1.5.1` and threw a runtime exception. Because the plugin boots on Scheduled Conference requests, that exception caused HTTP 500 responses in both frontend and backend Scheduled Conference pages.

The official Leconfe `1.5.0...1.5.1` diff does not modify the `PaymentManager::queue()` or `PaymentManager::fulfillQueued()` contracts used by this plugin. The relevant Leconfe 1.5.1 changes concern announcement scoping, contributor links, Unicode Scheduled Conference path decoding, static-page titles, and plugin-view permissions.

## What changed

- Added Leconfe `1.5.1` to the validated compatibility allow-list.
- Kept runtime reflection checks for `PaymentManager::queue()` and `PaymentManager::fulfillQueued()`.
- Updated package metadata and documentation to declare support for Leconfe 1.5.1.
- Added runtime compatibility coverage for `1.5.1`.
- No discount, coupon, PayPal, settlement, schema, or persisted-data behavior was changed.

## Data compatibility

No schema migration is required. Existing automatic rules, reasons, coupon campaigns, encrypted coupon codes, redemptions, payment snapshots, invoices, receipts, reports, and audit logs are preserved.

## Upgrade steps

1. Back up the database and current plugin directory.
2. Disable Conference Discount Eligibility temporarily.
3. Upload `ConferenceDiscountEligibility-1.4.1.zip` through Plugin Management.
4. Confirm version 1.4.1 is displayed and enabled.
5. Open a Scheduled Conference in both frontend and backend and confirm that neither returns HTTP 500.
6. Verify one Participant Payment and one Submission Payment before production use.

No direct/saved plugin URL, database rewrite, or Leconfe core modification is required.
