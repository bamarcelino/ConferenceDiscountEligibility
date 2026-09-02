# Upgrade to 1.4.0

Version 1.4.0 adds support for Leconfe 1.5.0 and continues to support Leconfe 1.4.6.

## What changed

- Leconfe 1.5 payment-required notifications now expose an integer `paymentId` instead of the public participant/submission model used by 1.4.6. The plugin resolves that Payment safely while retaining the legacy path.
- The compatibility guard now accepts both supported Leconfe releases and verifies the native `queue()` and `fulfillQueued()` method signatures.
- `composer test` is available for Leconfe 1.5's plugin test discovery.

## Data compatibility

There is no schema migration in 1.4.0. Existing automatic rules, free-text reasons, coupon campaigns, encrypted coupon codes, redemptions, payment snapshots, audit logs, and reports remain unchanged.

## Upgrade steps

1. Back up the database and current plugin directory.
2. Upload `ConferenceDiscountEligibility-1.4.0.zip` through Plugin Management.
3. Confirm version 1.4.0 is displayed and the plugin is enabled.
4. Open a Scheduled Conference through normal navigation.
5. Create or edit a test discount and coupon, then verify one Participant Payment and one Submission Payment before production use.

No saved/direct URL, manual registration, database rewrite, or core-file modification is required.
