<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Renders user-entered message / forum-post text as safe HTML:
 *   - escapes everything first (XSS-safe; only controlled markup is injected)
 *   - turns plain URLs into clickable links
 *   - replaces YouTube links with an embedded, watchable player
 *   - highlights @mentions
 *   - optionally converts newlines to <br> (skip when the container already
 *     uses white-space:pre-wrap, e.g. .wa-text / .post-body)
 */
class MessageFormatter
{
    public static function toHtml(?string $text, iterable $mentionNames = [], bool $nl2br = false): HtmlString
    {
        $names = [];
        foreach ($mentionNames as $n) {
            $n = trim((string) $n);
            if ($n !== '') $names[] = $n;
        }

        // Split on URLs so we can escape text segments but transform URL segments.
        $parts = preg_split('~(https?://[^\s<>"]+)~i', (string) $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $html = '';
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                $html .= self::renderUrl($part);
            } else {
                $html .= self::renderText($part, $names, $nl2br);
            }
        }

        return new HtmlString($html);
    }

    protected static function renderText(string $part, array $names, bool $nl2br): string
    {
        $esc = e($part);
        foreach ($names as $nm) {
            $esc = preg_replace(
                '/@' . preg_quote(e($nm), '/') . '\b/u',
                '<span class="wa-mention">@' . e($nm) . '</span>',
                $esc
            );
        }
        return $nl2br ? nl2br($esc) : $esc;
    }

    protected static function renderUrl(string $url): string
    {
        // Peel trailing punctuation that almost certainly isn't part of the URL.
        $trail = '';
        while ($url !== '' && strpbrk(substr($url, -1), '.,!?;:)]}') !== false) {
            $trail = substr($url, -1) . $trail;
            $url   = substr($url, 0, -1);
        }

        if ($vid = self::youtubeId($url)) {
            return '<div class="yt-embed"><iframe src="https://www.youtube-nocookie.com/embed/' . e($vid)
                . '" title="YouTube video player" frameborder="0" loading="lazy"'
                . ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"'
                . ' allowfullscreen></iframe></div>' . e($trail);
        }

        return '<a href="' . e($url) . '" target="_blank" rel="noopener nofollow ugc">' . e($url) . '</a>' . e($trail);
    }

    /** Extract the 11-char video id from common YouTube URL shapes, or null. */
    protected static function youtubeId(string $url): ?string
    {
        $patterns = [
            '~^https?://(?:www\.)?youtube\.com/watch\?(?:[^#]*&)?v=([A-Za-z0-9_-]{11})~i',
            '~^https?://(?:www\.)?youtube\.com/(?:embed|shorts|live|v)/([A-Za-z0-9_-]{11})~i',
            '~^https?://youtu\.be/([A-Za-z0-9_-]{11})~i',
            '~^https?://(?:www\.)?youtube-nocookie\.com/embed/([A-Za-z0-9_-]{11})~i',
        ];
        foreach ($patterns as $re) {
            if (preg_match($re, $url, $m)) return $m[1];
        }
        return null;
    }
}
