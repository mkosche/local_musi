## Version 0.6.6 (2023091401)
* Improvement: Show parent entitiy with entity
* Improvement: Upgrade all existing location fields in booking with new (parent) entity for better filtering.
## Version 0.6.5 (2023091400)
* Improvement: Moodle 4.2 compatibility issue with html_entity_decode.
* Improvement: Speed up table.
* Improvement: Show max answers as default.

## Version 0.6.4 (2023090800)
**New features:**
* New feature: Show all sports, their categories and their courses with the [showallsports] shortcode.

**Improvements:**
* Improvement: Always gender using a colon (":").
* Improvement: Even better sports divisions list, better styling, show in M:USI dashboard and fix some bugs.

**Bugfixes:**
* Bugfix: SAP files crash if lastname is null.
* Bugfix: Fix a bug where firstname and lastname of users were missing in easy availability modal.
* Bugfix: Fix display of empty tables.
* Bugfix: Add missing call of init.initprepageinline to table_list.

## Version 0.6.3 (2023083100)
**Improvements:**
* Improvement: Easy availability modal no longer gets locked with incompatible conditions.

## Version 0.6.2 (2023082300)
**New features:**
* New feature: Plugin config setting to turn descriptions in shortcodes lists on or off.
* New feature: Description can now be edited directly from new "Edit availability & description" modal.

**Bugfixes:**
* Bugfix: Fix teacher access for edit availability modal.

## Version 0.6.1 (2023081600)
**Bugfixes:**
* Bugfix: Added missing observers for mpay24 events.

## Version 0.6.0 (2023081100)
**New features:**
* New feature: Transaction list now supports Mpay24 too (besides PayUnity).

**Improvements:**
* Improvement: Show timecreated and timemodified in transactions list and fix support for more than one gateway.
* Improvement: Better transaction list with support for multiple gateways and sorting by timecreated DESC.
* Improvement: Add header string for gateway.

**Bugfixes:**
* Bugfix: Transactionslist does not crash anymore when unsupported gateways are used.
* Bugfix: Missing cache definitions.

## Version 0.5.9 (2023080700)
**Bugfixes:**
* Bugfix: Refactor: new location of dates_handler.

## Version 0.5.8 (2023072100)
**Improvements:**
* Improvement: Decision: we only show entity full name in location field.

## Version 0.5.7 (2023071700)
**Bugfixes:**
* Bugfix: Wrong class for editavailability dropdown item.

## Version 0.5.6 (2023071200)
**New features:**
* New feature: Easy availability form for M:USI.
* New feature: Better overview and accordion for SAP files.
* New feature: Send direct mails via mail client to all booked users.

**Improvements:**
* Improvement: Move option menu from mod_booking to local_musi and rename it to musi_bookingoption_menu.
* Improvement: Code quality - capabilities in musi_table.
* Improvement: MUSI-350 changes to SAP files.

**Bugfixes:**
* Bugfix: Make sure to only book for others if on cashier.php - else we always want to book for ourselves.
* Bugfix: Fixed link to connected Moodle course with shortcodes (cards and list).

## Version 0.5.5 (2023062300)
**Improvements:**
* Improvement: Nicer MUSI button (light instead of secondary).
* Improvement: Removed unnecessary moodle internal check.

**Bugfixes:**
* Bugfix: Some fixes for manual rebooking to keep table consistency.

## Version 0.5.4 (2023061600)
**New features:**
* New feature: New possibility for cashier to rebook users manually. In MUSI we can listen to the payment_rebooked event and write into the appropriate payment tables if necessary.

**Bugfixes:**
* Bugfix: Fix cashier typos.

## Version 0.5.3 (2023060900)
**Improvements:**
* Improvement: Sorting and filtering for payment transactions.

**Bugfixes:**
* Bugfix: SAP daily sums now show the sums that were ACTUALLY paid via payment gateway.
