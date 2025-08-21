# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v3.0.0] (2025-08-20)

### Added

* Responsive grid layout with device-specific columns (3 cols desktop, 2 cols tablet, 1 col mobile)
* Custom gap spacing for different screen sizes (80px large displays, 40px laptops, 30px tablets/mobile)
* 4:3 aspect ratio for featured images with responsive design
* Event status filtering in admin (All Events, Upcoming, Today's Events, Past Events)
* Location field with visual indicators and proper styling
* Comprehensive ACF dependency checking with detailed error messages
* Scroll hints and loading animations for better user experience
* Friendly "no more events" message with encouraging text and emojis
* Enhanced event card design with hover effects and modern styling
* Meta information display with styled date badges and time indicators

### Changed

* Updated initial event display from 9 to 6 events with 6-event loading increments
* Improved infinite scroll with better error handling and user feedback
* Enhanced admin interface with better column management and sorting (always ASC by date)
* Event cards now use flexbox layout for consistent heights across grid
* Date format standardized to 'Ymd' for reliable database comparisons
* Archive pages now automatically filter out past events (frontend only)
* ACF field registration moved to programmatic creation via PHP
* Improved responsive design with device-specific adjustments
* Better accessibility with focus states and reduced motion support

### Fixed

* Infinite scroll error handling - now shows proper "no more events" message instead of server errors
* Past events filtering - properly hides past events on frontend while keeping them accessible in admin
* Date comparison issues by using WordPress timezone (`current_time()`) instead of server time
* ACF Pro detection reliability using `acf_get_setting()` function
* Event ordering consistency - all queries now use ASC order by event date
* Template fallback handling when event card template is missing

### Removed

* All debugging code and console logging from production files
* Dependency on JSON field group files (now uses PHP registration)
* Unnecessary caching that could interfere with real-time event filtering
* Legacy ACF bundling code and restrictive activation logic

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

[v3.0.0]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v2.1.2...v3.0.0
[v2.1.2]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v2.1.1...v2.1.2
[v2.1.1]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v2.1.0...v2.1.1
[v2.1.0]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/releases/tag/v2.1.0
