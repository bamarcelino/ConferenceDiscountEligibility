# Conference Discount Eligibility 1.4.1

Patch release adding explicit compatibility with Leconfe 1.5.1 while preserving support for Leconfe 1.5.0 and 1.4.6.

## Fixed

- Prevents HTTP 500 errors on Leconfe 1.5.1 caused by the strict compatibility allow-list in version 1.4.0.
- Accepts Leconfe 1.5.1 after verifying that the PaymentManager contracts used by the plugin remain unchanged.

## Compatibility

- Leconfe 1.4.6
- Leconfe 1.5.0
- Leconfe 1.5.1
- PaypalPayment 1.1.0
- PHP 8.1 or later

## Data compatibility

No database migration is required. Existing discounts, free-text reasons, domains, coupon campaigns, encrypted coupon codes, redemptions, payment snapshots, invoices, receipts, reports and audit logs are preserved.

## Validation

The release package is generated only after the standalone regression suite, Leconfe version guard simulation, entrypoint smoke test, plugin discovery simulation, PaymentManager simulation, notification compatibility simulation, PHP/Blade lint, secret scan and archive validation all pass.
