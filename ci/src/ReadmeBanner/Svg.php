<?php

declare(strict_types=1);

namespace Webdevops\Build\ReadmeBanner;

use function htmlspecialchars;
use function rtrim;
use function sprintf;
use function str_contains;

final class Svg
{
    /**
     * Formats a coordinate with at most one decimal, so the output does not
     * depend on float noise and stays the same on every machine.
     */
    public static function number(float $value): string
    {
        $text = sprintf('%.1f', $value);
        if (str_contains($text, '.')) {
            $text = rtrim(rtrim($text, '0'), '.');
        }

        return $text === '-0' ? '0' : $text;
    }

    public static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
