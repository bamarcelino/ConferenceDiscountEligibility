# Conference Discount Eligibility 1.4.1

Patch release adding explicit compatibility with Leconfe 1.5.1 while preserving support for Leconfe 1.5.0 and 1.4.6.

The HTTP 500 observed on Leconfe 1.5.1 was caused by the strict compatibility allow-list in version 1.4.0. The PaymentManager contracts used by the plugin remain unchanged in Leconfe 1.5.1, so the release updates the compatibility guard without changing payment, coupon, settlement, or persistence behavior.

No database migration is required. Existing discounts, free-text reasons, coupon campaigns, encrypted coupon codes, redemptions, payment snapshots, invoices, receipts, reports, and audit logs are preserved.
