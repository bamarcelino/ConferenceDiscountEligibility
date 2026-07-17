# Upgrade to 1.2.1

## Purpose

Version 1.2.1 completes a native Leconfe Payment automatically when an automatic eligibility rule or payment-page coupon reduces the complete final total to exactly zero.

## Behavior

- The Payment amount is stored as `0.00`.
- Leconfe's native `PaymentManager::fulfillQueued()` records the paid state.
- The payment method is `full_discount`.
- No PayPal checkout is opened and no PayPal identifier is fabricated.
- Invoice, receipt, snapshot, report, and audit data remain available.
- A reserved 100% coupon is consumed immediately.
- The native Payment Confirmed notification is sent after commit.
- Contradictory Participant/Submission Payment Required notifications are suppressed.
- A positive add-on remainder remains payable through the official PayPal plugin.

## Database

No migration is introduced. Schema version 3 from 1.2.0 remains unchanged. Existing rules, coupons, reservations, snapshots, payments, invoices, receipts, and audit logs are preserved.

## Upgrade steps

1. Back up the database.
2. Confirm that no PayPal checkout is open for a payment being tested.
3. Disable Conference Discount Eligibility temporarily.
4. Upload `ConferenceDiscountEligibility-1.2.1.zip` over the existing plugin.
5. Enable the plugin.
6. Refresh the panel with `Ctrl + Shift + R`.
7. Confirm version 1.2.1 in Plugin Management.
8. Create a test coupon with 100% discount and apply it to an unpaid test Payment.

## Expected result

For a EUR 25.00 payment with no payable add-ons:

```text
Base fee: EUR 25.00
Discount: -EUR 25.00
Final total: EUR 0.00
Status: Completed
Payment method: Full Discount
PayPal payment ID: none
```

For base-only scope with a EUR 5.00 add-on:

```text
Base fee: EUR 25.00
Discount: -EUR 25.00
Add-ons: EUR 5.00
Final total: EUR 5.00
Status: Pending
Gateway: Paypal Payment 1.1.0
```
