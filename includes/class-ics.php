<?php

/**
 * "Add to Calendar" iCalendar (.ics) generator for Simple Events Calendar.
 *
 * Streams a standards-compliant .ics file for a single event so visitors can
 * add it to Apple Calendar, Outlook, Google Calendar (import), etc. The button
 * on the single-event template links to Simple_Events_ICS::url($event_id),
 * which is home_url() with a `sec_ical` query var; the request is intercepted
 * on template_redirect, the file is streamed, and execution stops.
 *
 * @package Simple_Events_Calendar
 * @since 5.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple_Events_ICS class
 */
class Simple_Events_ICS {

    /**
     * Query var that triggers an .ics download.
     */
    const QUERY_VAR = 'sec_ical';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('template_redirect', array($this, 'maybe_output'));
    }

    /**
     * Build the download URL for an event's .ics file.
     *
     * @param int $event_id Event post ID.
     * @return string
     */
    public static function url($event_id) {
        return add_query_arg(self::QUERY_VAR, (int) $event_id, home_url('/'));
    }

    /**
     * Intercept a `?sec_ical=<id>` request and stream the .ics file.
     */
    public function maybe_output() {
        if (!isset($_GET[self::QUERY_VAR])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $event_id = absint($_GET[self::QUERY_VAR]); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!$event_id) {
            return;
        }

        $event = get_post($event_id);
        if (!$event || 'simple-events' !== $event->post_type || 'publish' !== $event->post_status) {
            return;
        }

        // Honor WordPress password protection — never stream a protected
        // event's title/location/description via the .ics download.
        if (post_password_required($event)) {
            return;
        }

        $this->output_ics($event_id);
        exit;
    }

    /**
     * Send headers and echo the .ics document for an event.
     *
     * @param int $event_id Event post ID.
     */
    private function output_ics($event_id) {
        $ics = $this->build_ics($event_id);

        nocache_headers();
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="event-' . $event_id . '.ics"');

        echo $ics; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Build the VCALENDAR/VEVENT document for an event.
     *
     * @param int $event_id Event post ID.
     * @return string
     */
    private function build_ics($event_id) {
        $tz       = wp_timezone();
        $utc      = new DateTimeZone('UTC');
        $date_raw = (string) get_post_meta($event_id, 'event_date', true);       // Ymd
        $start_raw = (string) get_post_meta($event_id, 'event_start_time', true); // g:i a
        $end_raw   = (string) get_post_meta($event_id, 'event_end_time', true);   // g:i a

        $dtstart_line = '';
        $dtend_line   = '';

        $start = ('' !== $start_raw)
            ? DateTimeImmutable::createFromFormat('Ymd g:i a', $date_raw . ' ' . $start_raw, $tz)
            : false;

        if ($start instanceof DateTimeImmutable) {
            // Timed event — emit UTC instants.
            $end = ('' !== $end_raw)
                ? DateTimeImmutable::createFromFormat('Ymd g:i a', $date_raw . ' ' . $end_raw, $tz)
                : false;
            if (!$end instanceof DateTimeImmutable || $end <= $start) {
                $end = $start->add(new DateInterval('PT1H'));
            }
            $dtstart_line = 'DTSTART:' . $start->setTimezone($utc)->format('Ymd\THis\Z');
            $dtend_line   = 'DTEND:' . $end->setTimezone($utc)->format('Ymd\THis\Z');
        } else {
            // No start time — all-day event (DTEND is exclusive, so +1 day).
            $day = DateTimeImmutable::createFromFormat('!Ymd', $date_raw, $tz);
            if (!$day instanceof DateTimeImmutable) {
                $day = current_datetime();
            }
            $dtstart_line = 'DTSTART;VALUE=DATE:' . $day->format('Ymd');
            $dtend_line   = 'DTEND;VALUE=DATE:' . $day->add(new DateInterval('P1D'))->format('Ymd');
        }

        $summary     = $this->escape_text(get_the_title($event_id));
        $location    = $this->escape_text((string) get_post_meta($event_id, 'event_location', true));
        $description = $this->escape_text(wp_strip_all_tags((string) get_the_excerpt($event_id)));
        $url         = $this->escape_text(get_permalink($event_id));
        $host        = wp_parse_url(home_url(), PHP_URL_HOST);
        $uid         = 'sec-event-' . $event_id . '@' . ($host ? $host : 'simple-events');

        $lines = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Simple Events Calendar//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            $dtstart_line,
            $dtend_line,
            'SUMMARY:' . $summary,
        );

        if ('' !== $location) {
            $lines[] = 'LOCATION:' . $location;
        }
        if ('' !== $description) {
            $lines[] = 'DESCRIPTION:' . $description;
        }
        if ('' !== $url) {
            $lines[] = 'URL:' . $url;
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        // iCalendar requires CRLF line endings.
        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Escape a value for an iCalendar text field (RFC 5545).
     *
     * @param string $text Raw value.
     * @return string
     */
    private function escape_text($text) {
        $text = (string) $text;
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(array(';', ','), array('\\;', '\\,'), $text);
        $text = str_replace(array("\r\n", "\r", "\n"), '\\n', $text);
        return $text;
    }
}
