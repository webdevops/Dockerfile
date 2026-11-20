<?php

declare(strict_types=1);

namespace Webdevops\Build\DockerHub;

use RuntimeException;

use function explode;
use function file_get_contents;
use function in_array;
use function is_file;
use function preg_match;
use function preg_replace_callback;
use function sprintf;

/**
 * Links of the Docker Hub pages into this repository. Docker Hub does not resolve relative links, so a path such as
 * docs/images/php.md#development-variant-php-dev becomes a link to the file on GitHub, after checking that the file
 * and the heading exist.
 */
final readonly class Links
{
    public const string BASE = 'https://github.com/webdevops/Dockerfile/blob/master/';

    /**
     * @param string $rootPath the repository root
     */
    public function __construct(private string $rootPath)
    {
    }

    /**
     * The URL of a path in the repository, with an optional anchor; a URL with http:// or https:// stays as it is.
     */
    public function url(string $target): string
    {
        if (preg_match('#^https?://#', $target) === 1) {
            return $target;
        }

        [$path, $anchor] = explode('#', $target, 2) + [1 => null];
        $file = $this->rootPath . '/' . $path;
        if (!is_file($file)) {
            throw new RuntimeException(sprintf('%s in conf/dockerhub.yml: %s does not exist.', $target, $path));
        }

        if ($anchor !== null && !in_array($anchor, MarkdownAnchors::of((string)file_get_contents($file)), true)) {
            throw new RuntimeException(sprintf('%s in conf/dockerhub.yml: %s has no heading with the anchor #%s.', $target, $path, $anchor));
        }

        return self::BASE . $target;
    }

    /**
     * Markdown with the target of every link [text](target) passed through url().
     */
    public function rewrite(string $markdown): string
    {
        return (string)preg_replace_callback(
            '/\[([^\]]*)\]\(([^)\s]+)\)/',
            fn (array $matches): string => '[' . $matches[1] . '](' . $this->url($matches[2]) . ')',
            $markdown,
        );
    }
}
