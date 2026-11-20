<?php

declare(strict_types=1);

namespace Webdevops\Build\DockerHub;

use function explode;
use function mb_strtolower;
use function preg_match;
use function preg_replace;
use function str_replace;
use function str_starts_with;
use function strlen;
use function trim;

/**
 * The anchors that GitHub gives the headings of a Markdown file, e.g. #webdevopsnginx for "## webdevops/nginx".
 */
final class MarkdownAnchors
{
    /**
     * The anchor of every ATX heading (# to ######) outside fenced code blocks, in the order of the file. A repeated
     * anchor gets -1, -2, ... as on GitHub.
     *
     * @return list<string>
     */
    public static function of(string $markdown): array
    {
        $anchors = [];
        $count = [];
        $fence = null;
        foreach (explode("\n", $markdown) as $line) {
            if (preg_match('/^ {0,3}(`{3,}|~{3,})/', $line, $matches) === 1) {
                if ($fence === null) {
                    $fence = $matches[1];
                } elseif (str_starts_with($matches[1], $fence[0]) && strlen($matches[1]) >= strlen($fence) && trim($line) === $matches[1]) {
                    $fence = null;
                }

                continue;
            }

            if ($fence !== null || preg_match('/^ {0,3}#{1,6}[ \t]+(.*?)(?:[ \t]+#+)?[ \t]*$/', $line, $matches) !== 1) {
                continue;
            }

            $anchor = self::anchor($matches[1]);
            $number = $count[$anchor] ?? 0;
            $count[$anchor] = $number + 1;
            $anchors[] = $number === 0 ? $anchor : $anchor . '-' . $number;
        }

        return $anchors;
    }

    /**
     * GitHub's anchor of a heading: its text without link targets and code marks, in lower case, without
     * punctuation except - and _, with - for each space.
     */
    private static function anchor(string $heading): string
    {
        $text = (string)preg_replace('/!?\[([^\]]*)\]\([^)]*\)/', '$1', $heading);
        $text = mb_strtolower(str_replace('`', '', $text));
        $text = (string)preg_replace('/[^\p{L}\p{M}\p{N}\p{Pc} \-]/u', '', $text);

        return str_replace(' ', '-', $text);
    }
}
