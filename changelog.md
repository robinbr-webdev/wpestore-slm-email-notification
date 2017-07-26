# Changelog
All notable changes to this project will be documented in this file.

## [1.2] - 2017-07-26
### Fixed
- Fixed twice creation of the license key because of the eStore_notification_email_body_filter.

### Removed
- Removed show only latest subscription in each email.
- Removed expire previous subscription.

## [1.1] - 2017-07-17
### Added
- Inline documentation in each function
- Recurring payment email notification in eStore_notification_email_body_filter
- Added the global $slm_debug_logger that causes error in functions send_recurring_payment_email and eStore_notification_email_body_filter

### Changed
- function modification to make email notifications work using the custom email template
- change the version from 1.0 to 1.1

### Removed
- inline comments and commented scripts were removed