# Changelog
All notable changes to this project will be documented in this file.

## [1.2.2.2] - 2017-07-27
### Fixed
- Fixed the alt value in custom email template image logo.

### Removed
- Removed the code that modifies SLM manage table.
- Removed the function that copies to SLM plugin.

## [1.2.2.1] - 2017-07-27
### Changed
- Commented the code that copies class to SLM plugin.
- Cleaned the plugin for unused scripts.
- Cleaned the plugin for unnecessary comments.

## [1.2.2.0] - 2017-07-27
### Added
- Added support for {first_name},{last_name} and {slm_data} WP e-store email tags in recurring payment email settings.

### Removed 
- Removed the customized signature in the email.

### Fixed
- WP eStore email tags not showing values in recurring email notification.

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