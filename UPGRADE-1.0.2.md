# Upgrade to Conference Discount Eligibility 1.0.2

## Why this update exists

Live testing confirmed that the institutional-domain rule correctly matched `claec.org` but rejected the account because its Leconfe email was not verified. Version 1.0.2 adds a controlled alternative for users who are demonstrably authors in the same scheduled conference.

## Upgrade steps

1. Do not initiate PayPal checkout for a payment that will be recalculated.
2. Back up the database and plugin directory.
3. Disable Conference Discount Eligibility in Plugin Management.
4. Upload `ConferenceDiscountEligibility-1.0.2.zip` over the existing plugin.
5. Re-enable it and reload the panel with `Ctrl+F5`.
6. Confirm version `1.0.2`.
7. Open **Discount Eligibility → Institutional Domains → claec.org → Edit**.
8. Change **Institutional domain identity policy** to **Verified email or confirmed conference author**.
9. Enable **Recalculate eligible unpaid payments** and save.

## Expected result for the current test account

When the account is tied to a submitted, non-rejected work in `#Cultures 2027`, the recalculation should report one accepted confirmed-author match. The domain rule can then become the winning eligibility source without recreating an individual entitlement.

Expected audit evidence includes:

- `identity_policy: verified_email_or_confirmed_author`;
- `identity_verification_method: confirmed_author`;
- `author_evidence_source`;
- `author_evidence_submission_id`;
- the accepted submission status.

## Security boundary

The account merely selecting the Leconfe Author role is not sufficient because that role is self-assignable. Version 1.0.2 requires evidence tied to a real submission in the same scheduled conference. Verified email remains the recommended policy whenever reliable verification mail is available.

## Rollback

Reinstalling 1.0.1 is not recommended after schema v2 has been used. To disable the new behavior safely, leave 1.0.2 installed and set each domain back to **Verified email only**. The added column is harmless when the fallback is not selected.
