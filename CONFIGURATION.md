# Configuration

## Individual Entitlements

Select an existing user and configure percentage, reason, validity, status, notes, and optional maximum uses. The entitlement is linked to the exact `users.id`.

## Email Lists

Enter an exact email address. Matching is trim-normalized and case-insensitive. The original address remains available for display. When a matching user exists or later uses that account, the record is linked to the user.

## Institutional Domains

Enter only the domain, for example `universidade.edu`. Matching is boundary-safe:

- `user@universidade.edu` matches;
- `user@fakeuniversidade.edu` does not;
- `user@universidade.edu.example.com` does not.

Enable subdomains only when intended.

### Domain identity verification

**Verified email only** is the secure default. The exact email domain must match and `email_verified_at` must be present.

**Verified email or confirmed conference author** is an explicit fallback. An unverified user is accepted only when there is concrete submission-scoped evidence in the same scheduled conference: submission owner, linked participant with Author role, or exact normalized email in the submission author list. The self-assignable global Author role alone is not sufficient.

## Coupon Campaigns

Open **Discount Eligibility - Coupon Campaigns**.

A campaign contains:

- campaign name;
- secure generated code or administrator-defined code;
- percentage and reason;
- eligible Participant Payment and/or Submission Payment types;
- optional restriction to selected Payment Fees;
- validity start and end;
- active status;
- optional total-use limit;
- per-user use limit;
- notes.

### Code handling

Generated codes use a generic `CDE-...` prefix and cryptographic randomness. Custom codes accept 4-64 letters, numbers, hyphens, or underscores and are case-insensitive.

The full code appears only in the persistent success notification after creation or regeneration. Copy it then. The database stores a keyed hash and a masked hint, not the full code.

Do not rotate the Laravel `APP_KEY` without replacing active coupon campaigns, because the keyed hashes depend on that key.

### Payment-page redemption

Enable **Allow coupon entry on payment pages** in Settings. An unpaid Participant Payment or Submission Payment then shows a **Coupon** section before the payment gateway is opened.

When the user applies a code, the plugin validates it on the server, compares it with all automatic rules, and applies only the highest valid percentage. Discounts never stack.

Examples:

- automatic domain discount 30%, coupon 40% - coupon wins;
- direct user discount 50%, coupon 40% - user discount remains and the coupon is not reserved;
- reserved coupon 50%, second coupon 40% - the original 50% coupon remains.

A selected coupon is reserved for that payment. It is consumed when Leconfe marks the payment paid. It can be removed before payment activity begins, after which the best remaining automatic rule is recalculated.

### Coupon scope

The campaign's payment-type and fee restrictions decide where the code may be used. The global **Discount scope** still decides whether the percentage applies only to the base fee or also to explicitly eligible add-ons.

## Suggested presets

- 40% - CLAEC active member / Institutional partner affiliate / Research4Life Group A
- 30% - Research4Life Group B / Individual approval

Any percentage from 0.01% through 100.00% may be configured.

## Discount scope

Default: **Base fee only**, for participant-registration and submission fees.

Optional: **Base fee and eligible add-ons**. Enter the exact generated add-on keys. A blank list means no add-on discount.

## CSV eligibility import

Required columns:

```text
email,discount_percentage,reason,valid_from,valid_until,notes
```

Use Preview first, then Dry Run. Duplicate strategies are `ignore`, `update`, and `error`. Dates use ISO `YYYY-MM-DD` or ISO date-time values.

## Recalculation

Completed payments are never changed. Unpaid recalculation is explicit and defaults off because PaypalPayment 1.1.0 does not persist a checkout-start marker before redirect. Confirm no PayPal checkout is open before recalculating.

The result reports matched, discounted, unchanged, skipped, paid, failed, identity-rejected domain matches, and matches accepted through confirmed authorship. Candidate searches include unpaid participant and submission payments.
