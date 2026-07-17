# Incident report — discount not applied to Participant Payment #3

Date: 2026-07-17  
Affected plugin build: 1.0.0  
Corrective build: 1.0.1  
Target: Leconfe 1.4.6 / Paypal Payment 1.1.0

## Evidence observed in the target panel

- Participant Payment #3 was created for `bruno.marcelino@claec.org` with a EUR 30.00 participant fee and no discount.
- The payment-creation request reached the plugin: the Audit Log contains `discount_not_applied` with origin `payment_queue` at 03:14:44.
- The direct entitlement shown earlier belonged to another account, `brunomarcelino@claec.org` (without the dot). The later Audit Log shows that entitlement being deleted, but it does not show an `entitlement_created` entry for the exact dotted account.
- A `claec.org` domain rule exists, but the old interface did not reveal whether the matched account was rejected because its email was unverified, because the rule was otherwise ineligible, or because recalculation failed.
- Recalculation actions generated only `rule_payment_recalculation_completed`, without exposing matched/changed/failed counts.
- Opening the Audit Log detail route returned HTTP 500, hiding the evaluated-rule context.

## Conclusions

1. PaymentManager interception is active; this is not a failure to load the plugin.
2. The displayed direct entitlement did not target the same `users.id` as Participant Payment #3.
3. A domain entitlement must still require `email_verified_at`; this security requirement is not removed.
4. Version 1.0.0 had two genuine diagnostic/administrative defects: the Audit Log detail 500 and a generic recalculation-success message that could conceal zero changes or a caught exception.
5. The exact reason the existing domain recalculation did not change Payment #3 cannot be stated from the 1.0.0 UI because the detail page failed. Version 1.0.1 exposes the result explicitly.

## Corrective changes in 1.0.1

- Audit values are converted to safe scalar JSON states, including invalid UTF-8 substitution, before Filament renders them.
- Recalculation now reports matched, discounted, unchanged, skipped, already-paid, failed, and unverified-domain counts.
- Caught recalculation exceptions are sent to the Laravel log and represented by an append-only audit entry with a message fingerprint; secrets are not recorded.
- Creating, editing, or manually recalculating a rule can display the real result immediately.
- Edit-form recalculation toggles now execute rather than being discarded.
- User search labels include full name, exact email, and the authoritative user ID.

## Recovery test after the update

1. Keep Payment #3 unpaid and do not open a PayPal checkout for it.
2. Upload version 1.0.1 over the existing plugin folder and reload the panel.
3. Create an Individual Entitlement by selecting the exact option for `bruno.marcelino@claec.org`; confirm the shown numeric user ID.
4. Use 40%, keep the rule active, and enable recalculation.
5. The notification must report `matched: 1`, `discounted: 1`, and `failed: 0`.
6. Payment #3 should change from EUR 30.00 to EUR 18.00. Invoice 003 should then show the EUR 30.00 base fee, a EUR -12.00 discount line, and EUR 18.00 total.
7. If using only the domain rule, verify the account email first. The updated notification explicitly reports an unverified-domain rejection.

No completed payment is modified and no refund is initiated by this recovery procedure.
