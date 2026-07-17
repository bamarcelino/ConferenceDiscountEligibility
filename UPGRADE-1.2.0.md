# Upgrade to 1.2.0 - Coupon Campaigns and Payment-Page Redemption

## Scope

Version 1.2.0 adds secure coupon campaigns and a coupon-entry form to unpaid Participant Payment and Submission Payment pages. Automatic user, email, and domain eligibility rules continue to work unchanged. Discounts remain non-cumulative and the highest valid percentage wins.

## Before upgrading

1. Back up the Leconfe database and plugin directory.
2. Confirm the target is Leconfe 1.4.6 and Paypal Payment 1.1.0.
3. Confirm no PayPal checkout is open for a payment that may be recalculated.
4. Keep the current plugin installed. Do not delete its tables or rules.

## Upgrade

1. Disable Conference Discount Eligibility temporarily in Plugin Management.
2. Upload `ConferenceDiscountEligibility-1.2.0.zip` over the existing plugin folder.
3. Re-enable the plugin.
4. Refresh the panel with `Ctrl + Shift + R`.
5. Confirm version 1.2.0.
6. Open **Discount Eligibility - Settings** and confirm **Allow coupon entry on payment pages** is enabled.

On first enabled boot, the plugin idempotently upgrades schema version 3 and creates:

- `conference_discount_coupons`;
- `conference_discount_coupon_redemptions`;
- `coupon_campaign_id` on payment snapshots;
- `coupon_redemption_enabled` on conference discount settings.

Existing entitlements, domain rules, snapshots, payments, invoices, and audit logs are preserved.

## Create a coupon

1. Open **Discount Eligibility - Coupon Campaigns**.
2. Select **New Coupon Campaign**.
3. Choose automatic secure generation or enter a custom code.
4. Configure percentage, reason, validity, global limit, per-user limit, payment types, and optional payment-fee restrictions.
5. Save and copy the full code immediately. Only a masked hint is retained and shown later.

## User flow

1. The user completes participant registration or submission.
2. The Leconfe payment is created.
3. On Participant Payment or Submission Payment, the user enters the code in the **Coupon** section.
4. The server validates and reserves the coupon, recalculates the payment, updates the snapshot and invoice line, and records the audit event.
5. PayPal receives the resulting `Payment.amount` through the official Paypal Payment plugin.
6. When Leconfe marks the payment paid, the reservation becomes consumed.

## Validation checklist

- Test one participant payment and one submission payment.
- Test invalid, expired, inactive, exhausted, and wrong-fee coupons.
- Test a coupon lower than an automatic discount.
- Test removal before opening PayPal.
- Confirm Payment Detail, invoice, Audit Log, and Discount Payment Report.
- Complete a PayPal Sandbox payment and confirm amount, `paid_at`, PayPal metadata, receipt, and consumed coupon state.

## Rollback

Disabling the plugin stops coupon entry but does not remove data or reverse existing payment snapshots. Do not run migration rollback on a production installation merely to return to 1.1.0, because version 1.1.0 does not understand coupon snapshots. Restore the database backup for a full rollback.
