# Upgrade to 1.3.0

## Before upgrading

1. Back up the Leconfe database and `plugins/ConferenceDiscountEligibility` directory.
2. Confirm that no administrator is editing a discount rule or coupon campaign.
3. Confirm that no PayPal checkout is open for a payment that may be recalculated.

## Install

1. Upload `ConferenceDiscountEligibility-1.3.0.zip` through Leconfe 1.4.6 **Plugin Management - Upload Plugin**.
2. Confirm that version 1.3.0 appears immediately in the installed-plugins table and is enabled.
3. Navigate normally to a Scheduled Conference panel. The **Discount Eligibility** group must appear without opening a saved/direct URL.
4. Open one existing rule and verify its reason mapping before saving.

## Enablement change

Earlier releases relied on Leconfe's context-scoped `enabled` setting. A plugin enabled from one Plugin Management URL could therefore remain disabled in another panel context. Version 1.3.0 declares the plugin sitewide and creates a missing sitewide enabled setting during plugin discovery, before Leconfe decides whether to boot it. An explicitly disabled sitewide value is preserved. The toggle now controls one shared state from any Plugin Management page.

The plugin's actual pages, rules, and data remain Scheduled Conference scoped. It does not add discount resources to site or conference panels.

## Reason compatibility

No database migration or bulk data rewrite is performed.

- Existing `reason` values, payment snapshots, audit records, and exports remain unchanged.
- `CLAEC active member` maps to **Active member** and retains the original text as details.
- `Institutional partner affiliate` maps to **Institutional partner** and retains the original text as details.
- `Research4Life Group A` and `Research4Life Group B` map to **Research support program** and retain their original text as details.
- `Individual approval` remains **Individual approval**.
- Any other historical value is presented as a custom **Other** reason without losing its text.

Saving an edited legacy record stores the generic label and retained details in the existing `reason` field.

## Rollback

Version 1.3.0 does not change schema version 3. Rolling the files back to 1.2.1 leaves existing data readable. New generic/custom reason strings remain valid free-text reasons in older releases, although their structured editing controls will not be available.
