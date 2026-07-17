# Development history

This repository records the development of Conference Discount Eligibility for Leconfe 1.4.6 and Paypal Payment 1.1.0.

The project was developed before the GitHub repository existed. The version history was therefore reconstructed from the preserved source packages without inventing original commit dates.

## Versions

- 1.0.0 - Initial automatic eligibility discount engine for participant payments.
- 1.0.1 - Payment recalculation and Audit Log fixes.
- 1.0.2 - Institutional-domain identity policy with verified-email or confirmed-author evidence.
- 1.0.3 - Expanded exact author-evidence matching.
- 1.1.0 - Discounts extended to both Participant Payment and Submission Payment.
- 1.2.0 - Coupon Campaigns, secure coupon generation and payment-page redemption.
- 1.2.1 - Automatic native settlement of 100% discounts without opening PayPal.

## Reconstructed local commit sequence

```text
4e80ec9 feat: initial Conference Discount Eligibility 1.0.0
41c8642 fix: payment recalculation and audit details in 1.0.1
b711274 feat: confirmed-author domain validation in 1.0.2
a5e473a fix: expanded author evidence matching in 1.0.3
ab7d62e feat: discount submission and participant payments in 1.1.0
ee7a7a2 feat: coupon campaigns and payment-page redemption in 1.2.0
25004eb fix: settle 100 percent discounts without PayPal in 1.2.1
```

These hashes belong to the reconstructed local Git history generated from the preserved source packages. They are not claimed as contemporaneous commits from the original development dates.

## Current stable version

Version 1.2.1 is the current stable source package. Its validation report identifies the checks executed locally and the external target tests that remain dependent on the authenticated Leconfe and PayPal Sandbox environments.
