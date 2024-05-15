# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v2.1.2] (2024-05-15)

### Changed

- Updated the meta WP query from the shortcode and ajax file to display future events by just their date and not including their end time.

## [v2.1.1] (2024-05-15)

### Fixed

- Fixed an issue with the TIME meta query from the shortcode ajax files that caused the events not to display.
- Removed a var_dump PHP function that was forgotten on the shortcode file.

## [v2.1.0] (2024-05-06)

### Added

- Added CHANGELOG.md

### Changed

- Updated the WP_QUERY arguments to display events by future date and before their end time, if provided.
- Made some adjustments to the core files.
- Updated the required PHP version to 7.4 from 8.1
- Updated the required WordPress version to 6.0 from 6.2

### Removed

- Removed transient cache code.

## v2.0.0 (2024-05-02)

### Added

- Added LICENSE
- Added README.md
- Added .gitignore

### Changed

- Updated CSS file
- Updated the "No more events" message from the `simple-events-shortcode.php` file.

[v2.1.2]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v2.1.1...v2.1.2
[v2.1.1]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v2.1.0...v2.1.1
[v2.1.0]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/releases/tag/v2.1.0
