# Installation and upgrade

## Supported target

- Leconfe 1.4.6
- Paypal Payment 1.1.0
- PHP satisfying the Leconfe 1.4.6 application requirement
- PHP `zip` extension for the Leconfe Upload Plugin action

## Upgrade from 1.0.3

1. Back up the Leconfe database and plugin directory.
2. Do not start a PayPal checkout for any unpaid payment that will be recalculated.
3. In the Scheduled Conference panel, open **Plugin Management**.
4. Upload **`ConferenceDiscountEligibility-1.1.0.zip`** over the existing plugin.
5. Enable the plugin if the update disabled it, then reload the scheduled-conference panel.
6. Confirm version **1.1.0** in Plugin Management.
7. No new schema migration is required. Existing settings, rules, snapshots, audit logs, and the domain `identity_policy` are preserved.
8. Open **Discount Eligibility → Institutional Domains**, edit the intended domain, select the identity policy, and recalculate only after confirming no PayPal checkout is open.

If Chrome reports `File of invalid type`, use the previously supplied Chrome ZIP MIME helper. Do not upload the `.tar.gz`.

## Fresh installation

1. Back up the database.
2. Upload `ConferenceDiscountEligibility-1.1.0.zip` through **Upload Plugin**.
3. Confirm that the detected root folder is `ConferenceDiscountEligibility`.
4. Enable the plugin for the intended scheduled conference.
5. Reload the panel. The plugin creates its tables idempotently.
6. Review **Discount Eligibility → Settings**.
7. Create rules only in a staging/test scheduled conference first.

## Enabling author-confirmed domain validation

For a domain such as `claec.org`:

1. Edit the domain rule.
2. In **Domain identity verification**, select **Verified email or confirmed conference author**.
3. Save with **Recalculate eligible unpaid payments** enabled when safe.
4. The exact payment user must be either:
   - the owner of a submitted work in the same scheduled conference; or
   - linked to a submission in the same scheduled conference as a participant whose role is `Author`.
5. Draft/incomplete, declined, payment-declined, and withdrawn submissions are not accepted as author evidence.
6. Merely assigning or self-selecting the global/scheduled-conference `Author` role is not sufficient.

Expected audit statistics include `confirmed_author_domain_matches: 1` when an unverified email is accepted through confirmed authorship.

## Disable and uninstall

Disabling stops rule evaluation and UI registration on later requests. Financial snapshots and audit data are retained. Leconfe 1.4.6 does not expose a plugin pre-uninstall callback, so deleting the plugin folder does not automatically drop plugin tables.

## Post-upgrade verification

- Open an existing Audit Log detail record.
- Edit one test domain and confirm both identity-policy options are visible.
- Test a verified-email account with both a Participant Payment and a Submission Payment.
- Test an unverified account that owns or is linked as Author to a submitted work in the same scheduled conference.
- Confirm a user with only the self-assigned Author role is rejected.
- Confirm both payment types show the final amount, invoice line, snapshot evidence, audit entry, and Discount Payment Report type label.
- Complete PayPal Sandbox validation before production rollout.
