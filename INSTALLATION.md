# Installation and Upgrade

## Compatibility

- Leconfe 1.4.6
- Paypal Payment 1.1.0
- PHP compatible with the target Leconfe installation
- Laravel, Filament, and Livewire versions bundled by Leconfe 1.4.6
- PHP extensions required by Leconfe, plus JSON and mbstring

## Upgrade from 1.1.0 or earlier

1. Back up the Leconfe database and plugin directory.
2. Confirm no PayPal checkout is open for a payment that may be changed.
3. Open **Plugin Management** and disable Conference Discount Eligibility temporarily.
4. Upload `ConferenceDiscountEligibility-1.2.0.zip` over the existing plugin.
5. Enable the plugin.
6. Refresh with `Ctrl + Shift + R`.
7. Confirm version 1.2.0.
8. Open **Discount Eligibility - Settings** and review **Allow coupon entry on payment pages**.

The enabled plugin runs its idempotent schema installer. Schema version 3 adds coupon campaigns, coupon redemptions, the coupon snapshot foreign key, and the conference-level coupon setting. Existing records are preserved.

## First installation

1. Back up the database.
2. Keep Paypal Payment 1.1.0 installed and configured.
3. Open **Plugin Management - Upload Plugin**.
4. Upload `ConferenceDiscountEligibility-1.2.0.zip`.
5. Enable the plugin.
6. Open **Discount Eligibility - Settings**.
7. Keep **Base fee only** initially.
8. Create a test automatic entitlement and a test coupon campaign.
9. Create one unpaid Participant Payment and one unpaid Submission Payment.
10. Apply the coupon from each payment page and inspect Payment Detail, invoice, Audit Log, and Discount Payment Report.
11. Complete a PayPal Sandbox transaction before production use.

## ZIP MIME issue in Chrome

Leconfe 1.4.6's browser-side upload field accepts the exact MIME `application/zip`. Some Windows/Chrome combinations label valid ZIP files differently. If the panel reports **File of invalid type**, use the previously supplied Chrome ZIP MIME helper or apply the documented Leconfe upload MIME patch. This does not alter the plugin archive.

## Package structure

The installable archive contains exactly one root folder:

```text
ConferenceDiscountEligibility/
  index.php
  index.yaml
  composer.json
  vendor/autoload.php
  src/
  resources/
  lang/
  database/
  tests/
  documentation files
```

Do not upload the `.tar.gz`; Leconfe 1.4.6's official upload mechanism accepts ZIP only.

## Post-installation checks

- Coupon Campaigns appears under Discount Eligibility.
- Settings contains **Allow coupon entry on payment pages**.
- An unpaid payment page shows the Coupon section.
- Invalid codes are rejected without changing the payment.
- A valid winning code updates `Payment.amount`, adds a negative discount line, updates the snapshot and invoice, and creates a reserved redemption.
- A lower code does not replace a higher existing rule.
- A completed PayPal payment consumes the reservation and preserves PayPal metadata and receipt generation.

## Deactivation and rollback

Disabling the plugin hides coupon entry and stops automatic discount interception but does not delete data. Do not roll back schema version 3 on production merely to downgrade the code. Restore the pre-upgrade database backup for a full downgrade.
