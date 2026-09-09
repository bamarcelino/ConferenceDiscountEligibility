# Installation and Upgrade

## Compatibility

- Leconfe 1.4.6, 1.5.0 or 1.5.1
- Paypal Payment 1.1.0
- PHP compatible with the target Leconfe installation
- Laravel, Filament, and Livewire versions bundled by a supported Leconfe release
- PHP extensions required by Leconfe, plus JSON and mbstring

## Upgrade from 1.4.0

1. Back up the Leconfe database and plugin directory.
2. Open **Plugin Management** and disable Conference Discount Eligibility temporarily.
3. Upload `ConferenceDiscountEligibility-1.4.1.zip` over the existing plugin.
4. Confirm that the installed-plugins table shows version 1.4.1 as enabled.
5. Navigate normally to a Scheduled Conference panel.
6. Confirm that frontend and backend scheduled-conference pages load without HTTP 500.
7. Test one Participant Payment and one Submission Payment before production use.

Version 1.4.1 has no schema migration. Existing automatic rules, free-text reasons, coupon campaigns, encrypted coupon codes, redemptions, payment snapshots, reports, and audit logs are preserved.

## First installation

1. Back up the database.
2. Keep Paypal Payment 1.1.0 installed and configured.
3. Open **Plugin Management - Upload Plugin**.
4. Upload `ConferenceDiscountEligibility-1.4.1.zip`.
5. Confirm that it appears immediately as enabled in the installed-plugins table.
6. Open a Scheduled Conference normally and select **Discount Eligibility - Settings** from its navigation.
7. Keep **Base fee only** initially.
8. Create a test automatic entitlement and a test coupon campaign.
9. Create one unpaid Participant Payment and one unpaid Submission Payment.
10. Apply the coupon from each payment page and inspect Payment Detail, invoice, Audit Log, and Discount Payment Report.
11. Complete a PayPal Sandbox transaction before production use.

## Leconfe 1.5.1 compatibility note

Leconfe 1.5.1 changes scheduled-conference path decoding and plugin-view permissions, but it does not modify the native `PaymentManager::queue()` or `PaymentManager::fulfillQueued()` contracts used by Conference Discount Eligibility. Version 1.4.0 rejected 1.5.1 solely because its compatibility guard used an explicit version allow-list, causing the plugin to throw during scheduled-conference boot. Version 1.4.1 adds 1.5.1 to the validated allow-list.

## ZIP MIME issue in Chrome

Leconfe's browser-side upload field accepts the exact MIME `application/zip`. Some Windows/Chrome combinations label valid ZIP files differently. If the panel reports **File of invalid type**, use the previously supplied Chrome ZIP MIME helper or apply the documented Leconfe upload MIME patch. This does not alter the plugin archive.

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

Do not upload a `.tar.gz`; Leconfe's official upload mechanism accepts ZIP only.

## Post-installation checks

- Scheduled Conference frontend loads normally.
- Scheduled Conference backend loads normally.
- Coupon Campaigns appears under Discount Eligibility.
- New or regenerated coupon campaigns provide **Reveal code** in the campaign actions.
- Legacy campaigns show **Code unavailable** until an unused campaign is regenerated once.
- The plugin is listed immediately after upload and remains enabled when moving between Leconfe panel contexts.
- Reason forms show one required free-text field and no predefined choices.
- Settings contains **Allow coupon entry on payment pages**.
- An unpaid payment page shows the Coupon section.
- Invalid codes are rejected without changing the payment.
- A valid winning code updates `Payment.amount`, adds a negative discount line, updates the snapshot and invoice, and creates a reserved redemption.
- A lower code does not replace a higher existing rule.
- A completed PayPal payment consumes the reservation and preserves PayPal metadata and receipt generation.

## Deactivation and rollback

Disabling the plugin hides coupon entry and stops automatic discount interception but does not delete data. Do not roll back schema version 4 on production merely to downgrade the code. Restore the pre-upgrade database backup for a full downgrade.
