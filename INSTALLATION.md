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
4. Upload `ConferenceDiscountEligibility-1.3.2.zip` over the existing plugin.
5. Confirm that the installed-plugins table immediately shows version 1.3.2 as enabled.
6. Navigate normally to a Scheduled Conference panel; no saved/direct plugin URL is required.
7. Open **Discount Eligibility - Settings** and review **Allow coupon entry on payment pages**.

If Plugin Management still displays an earlier version after upload, reload the page and clear the application/PHP opcode cache or restart the PHP service according to the hosting environment. Test the corrected form only after version 1.3.2 is shown.

The enabled plugin runs its idempotent schema installer. Schema version 4 adds a nullable encrypted-code column to coupon campaigns. Existing campaigns, hashes, hints, redemptions, and payment snapshots are preserved.

## First installation

1. Back up the database.
2. Keep Paypal Payment 1.1.0 installed and configured.
3. Open **Plugin Management - Upload Plugin**.
4. Upload `ConferenceDiscountEligibility-1.3.2.zip`.
5. Confirm that it appears immediately as enabled in the installed-plugins table.
6. Open a Scheduled Conference normally and select **Discount Eligibility - Settings** from its navigation.
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
