# Upgrade to 1.3.2

## Purpose

Version 1.3.2 fixes the HTTP 500 error reported when opening create or edit discount forms on Leconfe 1.4.6. It also removes predefined Reason choices and lets the administrator write the complete reason directly.

## Install

1. Back up the Leconfe database and plugin directory.
2. Upload `ConferenceDiscountEligibility-1.3.2.zip` through Leconfe 1.4.6 Plugin Management.
3. Confirm that version 1.3.2 is enabled.
4. Open a Scheduled Conference and create or edit an individual, email, institutional-domain, and coupon discount.
5. Confirm that **Reason** is one required text field and that existing reasons appear unchanged.

Do not validate the corrected form while Plugin Management still displays an earlier version. If 1.3.2 is not shown after upload, reload Plugin Management and clear the application/PHP opcode cache or restart the PHP service according to the hosting environment before testing again.

## Data compatibility

No database migration is required for this change. The form remains bound to the existing 255-character `reason` column.

Existing reasons are not converted, categorized, or rewritten. Values created by all earlier versions, including CLAEC/Research4Life text and the generic labels introduced in 1.3.0, remain exactly as stored.

## Coupon-code recovery

Version 1.3.2 includes schema version 4 and the secure coupon-code recovery introduced in 1.3.1. Generated, manually entered, and regenerated coupon codes can be revealed by authorized administrators. Older one-way-hashed codes remain valid but cannot be reconstructed unless an unused campaign is regenerated.

## Rollback

Rolling application files back to 1.3.1 does not alter stored reasons. The 1.3.1 selector will interpret the existing text according to its legacy mapping behavior. Do not drop the nullable encrypted coupon-code column merely to roll back application files.
