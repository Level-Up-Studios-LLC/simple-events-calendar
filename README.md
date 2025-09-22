# Simple Events Calendar

A clean, responsive WordPress plugin for displaying events with infinite scroll and modern design. Built with simplicity and performance in mind.

## Description

Simple Events Calendar provides an elegant way to create and display events on your WordPress website. The plugin features a responsive grid layout that adapts to all screen sizes, infinite scroll loading, and automatic filtering to show only current and upcoming events.

## Features

### 🎨 **Modern Design**

- Responsive grid layout (3 columns desktop, 2 tablet, 1 mobile)
- 4:3 aspect ratio featured images
- Hover effects and smooth animations
- Clean, professional card-based design

### 📱 **Fully Responsive**

- Custom gap spacing for different screen sizes
- Device-optimized layouts
- Touch-friendly interactions
- Mobile-first approach

### ⚡ **Performance Optimized**

- Infinite scroll with AJAX loading
- Loads 6 events initially, then 6 more on scroll
- Optimized queries with proper caching
- Minimal resource usage

### 🎯 **Smart Filtering**

- Automatically hides past events on frontend
- Admin can view all events (past, present, future)
- Chronological ordering (upcoming events first)
- Category-based filtering

### 🛠 **Easy to Use**

- Simple shortcode: `[simple_events_calendar]`
- Custom post type with intuitive fields
- Built-in event categories
- No complex configuration needed

## Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher
- **Advanced Custom Fields**: Free or Pro version required

## Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/simple-events-calendar/`
3. Install and activate **Advanced Custom Fields** (Free or Pro)
4. Activate the **Simple Events Calendar** plugin
5. Start creating events!

## Usage

### Creating Events

1. Go to **Events > Add New** in your WordPress admin
2. Fill in the event details:
   - **Event Date** (required)
   - **Event Start Time** (required)
   - **Event End Time** (optional)
   - **Event Location** (optional)
3. Add a featured image for best results
4. Publish your event

### Displaying Events

**Using the Shortcode:**

```text
[simple_events_calendar]
```

**Shortcode Parameters:**

- `posts_per_page` - Number of events to display initially (default: 6)
- `category` - Filter by event category slug
- `show_past` - Show past events (default: 'no')
- `order` - Sort order (default: 'ASC')
- `orderby` - Sort by field (default: 'event_date')
- `show_time` - Display event times (default: 'yes')
- `show_excerpt` - Display event excerpts (default: 'yes')
- `show_location` - Display event locations (default: 'yes')
- `show_footer` - Display read more links (default: 'yes')

**Examples:**

```text
[simple_events_calendar posts_per_page="9"]
[simple_events_calendar category="workshops"]
[simple_events_calendar show_past="yes"]
[simple_events_calendar show_time="no" show_location="no"]
```

### Archive Pages

Events automatically appear on:

- `/events/` (main events archive)
- `/events/category/category-name/` (category archives)

## Event Fields

Each event includes these custom fields:

| Field            | Type        | Required | Description                 |
| ---------------- | ----------- | -------- | --------------------------- |
| Event Date       | Date Picker | Yes      | When the event takes place  |
| Event Start Time | Time Picker | Yes      | Event start time            |
| Event End Time   | Time Picker | No       | Event end time              |
| Event Location   | Text        | No       | Where the event takes place |

## Admin Features

- **Event Status Filtering**: View All, Upcoming, Today's, or Past events
- **Smart Columns**: Event thumbnail, date, time, location, and categories
- **Sortable Interface**: Click column headers to sort by date, time, or location
- **Quick Edit**: Fast editing of event details
- **Category Management**: Organize events with categories
- **Duplicate Prevention**: Admin columns prevent duplicate content display
- **ACF Dependency Check**: Clear error messages with download links if ACF is missing

## Responsive Breakpoints

| Screen Size           | Columns | Gap Size |
| --------------------- | ------- | -------- |
| Large (1367px+)       | 3       | 80px     |
| Laptop (769px-1366px) | 3       | 40px     |
| Tablet (481px-768px)  | 2       | 30px     |
| Mobile (≤480px)       | 1       | 30px     |

## Browser Support

- ✅ Chrome (latest 2 versions)
- ✅ Firefox (latest 2 versions)
- ✅ Safari (latest 2 versions)
- ✅ Edge (latest 2 versions)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## Accessibility

- WCAG 2.1 AA compliant
- Keyboard navigation support
- Screen reader friendly
- High contrast mode support
- Reduced motion respect

## Development

### File Structure

```text
simple-events-calendar/
├── simple-events-calendar.php         # Main plugin file
├── assets/
│   ├── css/
│   │   ├── simple-events.css          # Compiled styles
│   │   └── simple-events.scss         # Source SCSS
│   └── js/
│       ├── simple-events.js           # Main JavaScript
│       └── simple-events-shortcode.js # Shortcode-specific JS
├── includes/
│   ├── acf-json.php                   # ACF integration
│   ├── acf-settings-page.php          # Field definitions
│   ├── class-admin-columns.php        # Admin interface
│   ├── class-ajax.php                 # AJAX handlers
│   ├── class-main.php                 # Main plugin class
│   ├── class-post-type.php            # Post type registration
│   ├── class-shortcode.php            # Shortcode functionality
│   └── functions.php                  # Utility functions
├── languages/                         # Translation files
├── src/                               # Source files for build
│   ├── css/simple-events.scss         # Source SCSS
│   └── js/
│       ├── simple-events.js           # Source JavaScript
│       └── simple-events-shortcode.js # Source shortcode JS
└── template-parts/
    └── content-event-card.php         # Event card template
```

### Hooks & Filters

**Actions:**

- `simple_events_before_card` - Before event card output
- `simple_events_after_card` - After event card output

**Filters:**

- `simple_events_card_data` - Modify event card data
- `simple_events_query_args` - Modify event query arguments

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

For support and bug reports, please:

1. Check the [Issues](https://github.com/Level-Up-Studios-LLC/simple-events-calendar/issues) page
2. Create a new issue with detailed information
3. Include WordPress and plugin version numbers

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

Created by [Level Up Studios, LLC](https://www.levelupstudios.com/)

---

**Simple Events Calendar** - Making event management simple and beautiful. 🎉
