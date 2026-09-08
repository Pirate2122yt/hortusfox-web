<?php

/**
 * Class IcsModule
 *
 * Renders calendar items as a standard iCalendar (RFC 5545) feed, for
 * subscribing from an external calendar client or a dashboard's
 * generic "iCal" integration (e.g. Homarr's Calendar widget) - anything
 * that accepts a plain .ics URL.
 */
class IcsModule {
    const LINE_FOLD_LENGTH = 75;

    /**
     * @param $items An iterable of CalendarModel rows
     * @param $calendarName
     * @return string
     */
    public static function renderCalendar($items, $calendarName)
    {
        $lines = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//HortusFox//Calendar Feed//EN';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';
        $lines[] = 'X-WR-CALNAME:' . static::escapeText($calendarName);

        if (is_countable($items)) {
            foreach ($items as $item) {
                foreach (static::renderEvent($item) as $line) {
                    $lines[] = $line;
                }
            }
        }

        $lines[] = 'END:VCALENDAR';

        return static::foldLines($lines);
    }

    /**
     * Every date_from/date_till in this app is a plain date (see the
     * <input type="date"> calendar form) stored with a 00:00:00 time
     * component, and CalendarModel's own range query treats date_till
     * as the inclusive last day of the event. RFC 5545 all-day events
     * use an EXCLUSIVE end date, so date_till gets bumped by one day
     * here to represent the same inclusive last day correctly.
     *
     * @param $item A CalendarModel row
     * @return array Lines between (and including) BEGIN:VEVENT/END:VEVENT
     */
    private static function renderEvent($item)
    {
        $dtstart = date('Ymd', strtotime($item->get('date_from')));
        $dtend = date('Ymd', strtotime($item->get('date_till') . ' +1 day'));

        return [
            'BEGIN:VEVENT',
            'UID:' . static::eventUid($item->get('id')),
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            'DTSTART;VALUE=DATE:' . $dtstart,
            'DTEND;VALUE=DATE:' . $dtend,
            'SUMMARY:' . static::escapeText($item->get('name')),
            'CATEGORIES:' . static::escapeText($item->get('class_name')),
            'URL:' . url('/calendar'),
            'END:VEVENT'
        ];
    }

    /**
     * @param $id
     * @return string
     */
    private static function eventUid($id)
    {
        $host = parse_url((string)url('/'), PHP_URL_HOST);
        if ((!is_string($host)) || (strlen($host) === 0)) {
            $host = 'hortusfox.local';
        }

        return 'calendar-item-' . $id . '@' . $host;
    }

    /**
     * Escapes a plain-text value for use inside an ICS TEXT property,
     * per RFC 5545 section 3.3.11: backslashes, commas, semicolons and
     * newlines all need escaping, in that order (escaping the
     * backslashes first, or they'd double-escape the ones just added
     * for the other characters).
     *
     * @param $value
     * @return string
     */
    private static function escapeText($value)
    {
        $value = (string)$value;
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace([',', ';'], ['\\,', '\\;'], $value);
        $value = str_replace(["\r\n", "\n", "\r"], '\\n', $value);

        return $value;
    }

    /**
     * RFC 5545 caps a content line at 75 octets and continues it on the
     * next physical line, indented by one space - long plant/event
     * names would otherwise produce a line some parsers choke on.
     *
     * @param $lines
     * @return string
     */
    private static function foldLines($lines)
    {
        $out = [];

        foreach ($lines as $line) {
            $bytes = strlen($line);

            if ($bytes <= self::LINE_FOLD_LENGTH) {
                $out[] = $line;
                continue;
            }

            $chunk = substr($line, 0, self::LINE_FOLD_LENGTH);
            $rest = substr($line, self::LINE_FOLD_LENGTH);
            $out[] = $chunk;

            while (strlen($rest) > 0) {
                // Continuation lines are one octet narrower to make
                // room for the leading space that marks them as such.
                $chunk = substr($rest, 0, self::LINE_FOLD_LENGTH - 1);
                $rest = substr($rest, self::LINE_FOLD_LENGTH - 1);
                $out[] = ' ' . $chunk;
            }
        }

        return implode("\r\n", $out) . "\r\n";
    }
}
