<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests;

use RuntimeException;
use Webdevops\Build\ImageTests;

use function explode;
use function getenv;
use function in_array;
use function is_string;
use function preg_match;
use function sprintf;

/**
 * The image the tests run against, with the settings that ci/console test:image passes in environment variables.
 */
final readonly class ImageUnderTest
{
    /**
     * @param string $name e.g. webdevops/php-nginx:8.5
     * @param string $reference the image to start, e.g. a local build of $name
     * @param string $osFamily alpine, debian or ubuntu
     * @param string $osVersion e.g. 3, 12 or 22.04; empty for the Alpine tags of the base images
     * @param bool $phpOfficial whether the image builds on the official PHP image
     */
    public function __construct(
        public string $name,
        public string $reference,
        public string $osFamily,
        public string $osVersion,
        public bool $phpOfficial,
    ) {
    }

    public static function fromEnvironment(): self
    {
        $name = self::environment('NAME');
        if ($name === '') {
            throw new RuntimeException('Run the image tests with "ci/console test:image webdevops/<image>:<tag>", which sets IMAGE_TEST_NAME and the other settings.');
        }

        return new self(
            $name,
            self::environment('REFERENCE') ?: $name,
            self::environment('OS_FAMILY'),
            self::environment('OS_VERSION'),
            self::environment('PHP_OFFICIAL') === '1',
        );
    }

    /**
     * The tag, e.g. 8.5-alpine.
     */
    public function tag(): string
    {
        return explode(':', $this->name, 2)[1] ?? '';
    }

    /**
     * PHP version of a PHP image, from its tag: 8.5 for 8.5 and 8.5-alpine.
     */
    public function phpMinorVersion(): string
    {
        $version = explode('-', $this->tag(), 2)[0];
        if (!preg_match('/^\d+\.\d+$/', $version)) {
            throw new RuntimeException(sprintf('%s has no PHP version in its tag.', $this->name));
        }

        return $version;
    }

    public function isAlpine(): bool
    {
        return $this->osFamily === 'alpine';
    }

    /**
     * Debian and Ubuntu share apt, dpkg and the locales.
     */
    public function isDebianOrUbuntu(): bool
    {
        return in_array($this->osFamily, ['debian', 'ubuntu'], true);
    }

    /**
     * The images have the imap extension up to PHP 8.3; PHP 8.4 no longer ships it.
     */
    public function hasImap(): bool
    {
        return (bool)preg_match('/^8\.[123]$/', $this->phpMinorVersion());
    }

    private static function environment(string $setting): string
    {
        $value = getenv(ImageTests::ENVIRONMENT_PREFIX . $setting);
        return is_string($value) ? $value : '';
    }
}
