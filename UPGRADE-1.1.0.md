# Upgrade to 1.1.0

## Purpose

Version 1.1.0 expands Conference Discount Eligibility from participant-registration fees to **all native payment types in Leconfe 1.4.6**:

- Participant Fee (`TYPE_PARTICIPANT_FEE`)
- Submission Fee (`TYPE_SUBMISSION_FEE`)

The official Paypal Payment 1.1.0 plugin remains unchanged and receives the final discounted `Payment.amount`.

## Preserved behavior

- Existing eligibility rules and domain identity policies remain unchanged.
- The largest valid discount continues to win.
- Calculations remain server-side and use integer minor units.
- Completed payments and payments with recorded payment activity are never recalculated.
- Base fee is discounted by default. Add-ons follow the existing **Discount scope** setting.
- Existing snapshots, invoices, reports, and audit logs are retained.

## Upgrade steps

1. Confirm no PayPal checkout is open for a payment that may be recalculated.
2. Back up the database and the existing plugin directory.
3. In **Plugin Management**, disable Conference Discount Eligibility temporarily.
4. Upload `ConferenceDiscountEligibility-1.1.0.zip` over the existing plugin.
5. Enable the plugin and refresh the panel with `Ctrl + Shift + R`.
6. Confirm version **1.1.0**.
7. Open the applicable individual, email, or domain rule and run **Recalculate unpaid payments**.
8. Check both Participant Payment and Submission Payment details, invoices, Audit Log, and Discount Payment Report.

No database migration is introduced by this release.

## Expected submission-payment result

For a €25 submission fee and a 40% rule:

```text
Standard submission fee: €25.00
Discount: 40%
Discount amount: -€10.00
Final amount: €15.00
```

The invoice should retain the original fee as a positive line and add one negative discount line. PayPal must receive €15.00.

## Session note

Use separate Chrome profiles or an incognito window when testing administrator and participant/author accounts simultaneously. Logging in as another user in the same browser profile can invalidate the Livewire CSRF token and display “This page has expired”.
