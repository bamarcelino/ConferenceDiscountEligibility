# Installation

## Supported target

- Leconfe 1.4.6
- Paypal Payment 1.1.0
- PHP satisfying the Leconfe 1.4.6 application requirement (`^8.1`)
- PHP `zip` extension for the Leconfe Upload Plugin action

## New installation

1. Back up the Leconfe database and plugin directory.
2. In the Scheduled Conference panel, open **Plugin Management**.
3. Upload **`ConferenceDiscountEligibility-1.0.2.zip`**.
4. Confirm that the detected folder is `ConferenceDiscountEligibility`.
5. Enable the plugin for the intended scheduled conference.
6. Reload the panel. The plugin boot installer creates or upgrades its tables idempotently.
7. Open **Discount Eligibility → Settings** and review the default base-fee-only scope.
8. Create a test rule and generate an unpaid participant registration before enabling production rules.

Leconfe 1.4.6 rejects `.tar.gz` in Upload Plugin. The supplied tarball is an additional source/distribution artifact, not the panel upload file.

## Upgrade from 1.0.0 or 1.0.1

1. Back up the database and plugin directory.
2. Do **not** uninstall the current plugin and do not delete its tables.
3. In Plugin Management, disable the plugin briefly.
4. Upload `ConferenceDiscountEligibility-1.0.2.zip` through the same update flow.
5. Re-enable the plugin and reload with `Ctrl+F5`.
6. Confirm that the displayed version is `1.0.2`.
7. Open the target institutional-domain rule and choose the required identity policy.

The upgrade adds one nullable-safe/defaulted domain-policy column. Existing rules are preserved and default to `verified_email_only`; no entitlement, snapshot, audit record, or payment is deleted.

## Disable and uninstall

Disabling stops rule evaluation and UI registration on subsequent requests. Data is retained. Leconfe 1.4.6 has no plugin uninstall callback, so removing the plugin folder does not drop tables. This prevents accidental loss of financial audit records.

## Post-install verification

- Check the Settings and Institutional Domains pages.
- Verify access with an authorized and unauthorized account.
- Test a verified-email domain rule.
- Test the opt-in confirmed-author fallback with an unverified account that owns or is listed on a submitted work in the same scheduled conference.
- Recalculate only an unpaid payment with no open PayPal checkout.
- Confirm Payment Detail, invoice, audit evidence, and the Discount Payment Report.
- Confirm the PayPal action receives the final Payment amount in Sandbox.
