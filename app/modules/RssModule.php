<?php

/**
 * Class RssModule
 *
 * Renders chat system messages (new plants, comments, task activity,
 * calendar changes, etc. - anything TextBlockModule already posts to
 * the Chat tab) as a standard RSS 2.0 feed, for subscribing from an
 * RSS reader or a dashboard's generic RSS widget (e.g. Homarr's RSS
 * Feed widget) - anything that accepts a plain feed URL.
 */
class RssModule {
    /**
     * @param $messages An iterable of ChatMsgModel rows (sysmsg = 1)
     * @param $channelTitle
     * @param $channelDescription
     * @param $channelLink
     * @return string
     */
    public static function renderFeed($messages, $channelTitle, $channelDescription, $channelLink)
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<rss version="2.0">';
        $lines[] = '<channel>';
        $lines[] = '<title>' . static::escapeXml($channelTitle) . '</title>';
        $lines[] = '<link>' . static::escapeXml($channelLink) . '</link>';
        $lines[] = '<description>' . static::escapeXml($channelDescription) . '</description>';
        $lines[] = '<generator>HortusFox</generator>';
        $lines[] = '<lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>';

        if (is_countable($messages)) {
            foreach ($messages as $message) {
                foreach (static::renderItem($message, $channelLink) as $line) {
                    $lines[] = $line;
                }
            }
        }

        $lines[] = '</channel>';
        $lines[] = '</rss>';

        return implode("\n", $lines) . "\n";
    }

    /**
     * A system message is already HTML (e.g. 'Alice left a comment on
     * <a href="...">Aloe</a>: "nice plant"'), built by TextBlockModule
     * from an __()-templated string. It's purified here the same way
     * the chat view purifies it before display, since not every
     * TextBlockModule caller escapes its own interpolated values (an
     * admin-entered plant name, say) and this is about to leave the
     * app as a feed rather than render inside it.
     *
     * @param $message A ChatMsgModel row
     * @param $fallbackLink Used when the message has no embedded link
     * @return array Lines between (and including) <item>/</item>
     */
    private static function renderItem($message, $fallbackLink)
    {
        $html = UtilsModule::purify((string)$message->get('message'));
        $link = static::extractLink($html) ?? $fallbackLink;
        $title = static::plainTextTitle($html);
        $pubDate = date(DATE_RSS, strtotime($message->get('created_at')));

        return [
            '<item>',
            '<title>' . static::escapeXml($title) . '</title>',
            '<link>' . static::escapeXml($link) . '</link>',
            '<guid isPermaLink="false">' . static::escapeXml(static::itemGuid($message->get('id'))) . '</guid>',
            '<pubDate>' . $pubDate . '</pubDate>',
            '<description>' . static::cdata($html) . '</description>',
            '</item>'
        ];
    }

    /**
     * @param $id
     * @return string
     */
    private static function itemGuid($id)
    {
        $host = parse_url((string)url('/'), PHP_URL_HOST);
        if ((!is_string($host)) || (strlen($host) === 0)) {
            $host = 'hortusfox.local';
        }

        return 'chat-msg-' . $id . '@' . $host;
    }

    /**
     * Pulls the first link out of a system message's HTML, if it has
     * one - most system messages link straight to the plant, task or
     * calendar item they're about.
     *
     * @param $html
     * @return string|null
     */
    private static function extractLink($html)
    {
        if (preg_match('/href="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Strips the message down to plain text for the item's <title>:
     * tags removed, entities decoded (they'd otherwise get double
     * escaped by escapeXml()), whitespace collapsed.
     *
     * @param $html
     * @return string
     */
    private static function plainTextTitle($html)
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if (mb_strlen($text) > 200) {
            $text = mb_substr($text, 0, 200) . '…';
        }

        return $text;
    }

    /**
     * @param $value
     * @return string
     */
    private static function escapeXml($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Wraps a value in a CDATA section, escaping any literal "]]>" it
     * might contain so it can't prematurely close the section.
     *
     * @param $value
     * @return string
     */
    private static function cdata($value)
    {
        return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', (string)$value) . ']]>';
    }
}
