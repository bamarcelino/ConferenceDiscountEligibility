# Installation

## Supported target

- Leconfe 1.4.6
- Paypal Payment 1.1.0
- PHP satisfying the Leconfe 1.4.6 application requirement (`^8.1`)
- PHP `zip` extension for the Leconfe Upload Plugin action

## Panel installation

1. Back up the Leconfe database and plugin directory.
2. In the Scheduled Conference panel, open Plugin Management.
3. Upload **`ConferenceDiscountEligibility-1.0.0.zip`**.
4. Confirm that the detected folder is `ConferenceDiscountEligibility`.
5. Enable the plugin for the intended scheduled conference.
6. Reload the panel. The plugin boot installer creates its tables idempotently.
7. Open **Discount Eligibility → Settings** and review the default base-fee-only scope.
8. Create a test entitlement and generate an unpaid participant registration before enabling production rules.

Leconfe 1.4.6 rejects `.tar.gz` in Upload Plugin. The supplied tarball is an additional source/distribution artifact, not the panel upload file.

The package is a staging validation candidate. Perform the post-install verification below in an isolated clone or staging scheduled conference before enabling production rules; the authenticated panel/Sandbox checks could not be executed in the build environment.

## Upgrade

Upload the newer ZIP through the same plugin-management update flow. Keep a database backup. Schema upgrades are versioned and idempotent.

## Disable and uninstall

Disabling stops rule evaluation and UI registration on subsequent requests. Data is retained. Leconfe 1.4.6 has no plugin uninstall callback, so removing the plugin folder does not drop tables. This prevents accidental loss of financial audit records.

## Post-install verification

- Check the Settings page.
- Verify access with an authorized and unauthorized account.
- Create a 40% test rule for a test user.
- Register using an EUR fee and confirm the Payment Detail snapshot.
- Confirm the PayPal action receives the final Payment amount in Sandbox.
- Confirm invoice, receipt, and the Discount Payment Report.
