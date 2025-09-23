=== Simple Events Calendar ===
Contributors: levelupstudios
Donate link: https://www.levelupstudios.com/
Tags: events, calendar, acf, advanced custom fields, shortcode, responsive
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 4.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple, responsive events calendar plugin for WordPress that integrates seamlessly with Advanced Custom Fields.

== Description ==

Simple Events Calendar is a lightweight, user-friendly events management plugin that creates beautiful, responsive event displays on your WordPress site. Built specifically to work with Advanced Custom Fields (ACF), it provides a clean and intuitive way to manage and display events.

= Key Features =

* **Responsive Design**: Automatically adapts to different screen sizes (3 columns on desktop, 2 on tablet, 1 on mobile)
* **Advanced Custom Fields Integration**: Seamlessly works with ACF Free or Pro
* **Infinite Scroll Loading**: Events load smoothly as users scroll down
* **Flexible Display Options**: Control what event information to show (time, excerpt, location, etc.)
* **Event Status Filtering**: Filter events by upcoming, today's events, or past events in admin
* **Clean Shortcode**: Simple `[sec_events]` shortcode with customizable parameters
* **Modern Build System**: SCSS compilation with development and production builds
* **Accessibility Ready**: Built with accessibility best practices and reduced motion support

= Shortcode Usage =

Display events anywhere on your site with the simple shortcode:

`[sec_events]`

= Shortcode Parameters =

* `posts_per_page` - Number of events to load initially (default: 6)
* `show_time` - Display event time (default: true)
* `show_excerpt` - Display event excerpt (default: true)
* `show_location` - Display event location (default: true)
* `show_footer` - Display event footer (default: true)

Example: `[sec_events posts_per_page="9" show_time="false"]`

= Required Plugin =

This plugin requires Advanced Custom Fields (ACF) to be installed and activated. It works with both the free and pro versions of ACF.

= Developer Features =

* SCSS build system with Sass compiler
* CSS linting with stylelint
* File watching for development
* Automated distribution creation
* Semantic versioning support

= Multilingual Support =

The plugin includes translations for:
* English (default)
* Spanish (es_ES)
* French (fr_FR)

Additional languages can be added using standard WordPress translation methods.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/simple-events-calendar` directory, or install the plugin through the WordPress plugins screen directly.
2. Install and activate Advanced Custom Fields (Free or Pro) if not already installed.
3. Activate the Simple Events Calendar plugin through the 'Plugins' screen in WordPress.
4. The plugin will automatically create the necessary custom fields for events.
5. Start creating events in your WordPress admin under "Events".
6. Use the `[sec_events]` shortcode to display events on any page or post.

== Frequently Asked Questions ==

= Do I need Advanced Custom Fields Pro? =

No, the plugin works with both ACF Free and ACF Pro. However, ACF (Free or Pro) is required for the plugin to function.

= Can I customize the event display? =

Yes, the plugin includes several shortcode parameters to control what information is displayed. You can also customize the styling through your theme's CSS.

= How many events are displayed by default? =

The plugin displays 6 events initially and loads 6 more events each time the user scrolls to the bottom (infinite scroll).

= Can I show past events? =

Past events are hidden on the frontend by default but remain accessible in the WordPress admin. The plugin automatically filters to show only current and upcoming events to site visitors.

= Is the plugin responsive? =

Yes, the plugin is fully responsive and adapts to different screen sizes automatically.

== Screenshots ==

1. Event display with responsive grid layout
2. WordPress admin events list with custom columns
3. Event creation form with ACF fields
4. Shortcode parameters and usage examples

== Changelog ==

= 4.3.0 (2024-09-22) =

**Added**
* **Multilingual Support**: Added complete translation support for Spanish (es_ES) and French (fr_FR)
* Added professional-quality translations for all plugin strings
* Added .po and .mo files for Spanish and French languages
* Added internationalization documentation and setup guide

**Changed**
* Updated .pot file with current version and creation date
* Enhanced plugin description to highlight multilingual capabilities
* Updated documentation to include translation information

= 4.2.4 (2024-09-22) =

**Added**
* Added SCSS build system with Sass compiler and stylelint
* Added development and production CSS build scripts
* Added file watching capabilities for development
* Added CSS linting with stylelint and standard SCSS configuration
* Added automatic version synchronization between plugin header, constants, and package.json

**Changed**
* **BREAKING**: Updated shortcode from `[simple_events_calendar]` to `[sec_events]` for consistency
* Improved responsive design with better media query organization
* Enhanced theme color inheritance for better integration with WordPress themes
* Refactored CSS build process from simple file copying to proper SCSS compilation
* Updated build system to generate both compressed (production) and expanded (development) CSS
* Improved accessibility features and reduced motion support

**Fixed**
* Fixed plugin description to clearly specify ACF® requirement
* Improved WordPress version compatibility requirements
* Enhanced color definitions and margin adjustments for better layout consistency
* Fixed media query structure for improved readability and maintainability

**Development**
* Added source maps for development builds
* Added proper CSS minification for production builds
* Added automated distribution and zip creation process

= 4.1.1 (2024-09-22) =

**Removed**
* Removed "Showing current and upcoming events only." message from shortcode display
* Removed scroll hint bar from after events
* Events now load automatically on scroll without instructional messages

= 4.1.0 (2024-09-22) =

**Improved**
* Enhanced ACF dependency error message for better user experience
* Added direct download link button "Download ACF Free Plugin" to WordPress plugin installer
* Cleaner, more actionable error messages for missing dependencies

= 4.0.3 (2024-09-22) =

**Fixed**
* Fixed duplicate content appearing in admin columns
* Added static tracking to prevent duplicate processing
* Eliminated stacked duplicate content within individual admin columns

= 4.0.0 (2024-09-22) =

**Added**
* Updated WordPress minimum requirement to 6.0+ for better compatibility
* Updated PHP minimum requirement to 7.4+
* Added build system with distribution and zip commands
* Added automated semantic versioning instructions

**Changed**
* **BREAKING**: Increased minimum WordPress and PHP requirements
* Refactored plugin architecture with proper class-based structure
* Improved admin columns functionality with better duplicate prevention

= 3.0.0 (2024-08-20) =

**Added**
* Responsive grid layout with device-specific columns
* Custom gap spacing for different screen sizes
* 4:3 aspect ratio for featured images with responsive design
* Event status filtering in admin
* Location field with visual indicators
* Comprehensive ACF dependency checking
* Scroll hints and loading animations
* Enhanced event card design with hover effects

**Changed**
* Updated initial event display from 9 to 6 events
* Improved infinite scroll with better error handling
* Enhanced admin interface with better column management
* Event cards now use flexbox layout for consistent heights
* Improved responsive design with device-specific adjustments

**Fixed**
* Infinite scroll error handling
* Past events filtering
* Date comparison issues using WordPress timezone
* ACF Pro detection reliability
* Event ordering consistency

== Upgrade Notice ==

= 4.2.4 =
BREAKING CHANGE: Shortcode changed from [simple_events_calendar] to [sec_events]. Please update your shortcodes after upgrading.

= 4.0.0 =
This version requires WordPress 6.0+ and PHP 7.4+. Please ensure your site meets these requirements before upgrading.

= 3.0.0 =
Major redesign with responsive grid layout and improved user experience. Backup your site before upgrading.