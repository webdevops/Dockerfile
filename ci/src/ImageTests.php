<?php

declare(strict_types=1);

namespace Webdevops\Build;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

use function is_array;
use function is_file;
use function is_string;
use function preg_match;
use function preg_match_all;
use function sprintf;
use function str_replace;
use function ucwords;

/**
 * The image tests in ci/image-tests: which image has them, and the settings they run with.
 */
final readonly class ImageTests
{
    /**
     * Directory of the test classes, relative to the repository root. The tests of webdevops/php-nginx are in
     * PhpNginxTest.php.
     */
    public const string DIRECTORY = 'ci/image-tests/Images';

    /**
     * Prefix of the environment variables that pass the settings to the tests, e.g. IMAGE_TEST_OS_FAMILY.
     */
    public const string ENVIRONMENT_PREFIX = 'IMAGE_TEST_';

    /**
     * @param string $rootPath the repository root
     */
    public function __construct(private string $rootPath = __DIR__ . '/../..')
    {
    }

    /**
     * The test class of $image relative to the repository root, e.g. ci/image-tests/Images/PhpNginxTest.php for
     * php-nginx; null if the image has no tests.
     */
    public function testFile(string $image): ?string
    {
        $file = self::DIRECTORY . '/' . str_replace(' ', '', ucwords(str_replace('-', ' ', $image))) . 'Test.php';
        return is_file($this->rootPath . '/' . $file) ? $file : null;
    }

    /**
     * The settings of webdevops/<image>:<tag> from "dockerTest" in conf/console.yml, e.g.
     * ['OS_FAMILY' => 'alpine', 'OS_VERSION' => '3', 'PHP_OFFICIAL' => '1']. OS_FAMILY and OS_VERSION come from the
     * tag or the defaults; the first entry of "image" whose pattern matches adds or replaces settings.
     *
     * @return array<string, string>
     */
    public function settings(string $name): array
    {
        $configuration = Yaml::parseFile($this->rootPath . '/conf/console.yml')['dockerTest']['configuration'] ?? null;
        if (!is_array($configuration) || !is_string($configuration['imageConfigurationRegex'] ?? null) || !is_array($configuration['default'] ?? null)) {
            throw new RuntimeException('conf/console.yml needs "dockerTest: configuration" with "imageConfigurationRegex" and "default".');
        }

        preg_match_all('/' . $configuration['imageConfigurationRegex'] . '/', $name, $matches);
        $settings = [
            'OS_FAMILY' => (string)($matches['OS_FAMILY'][0] ?? $configuration['default']['OS_FAMILY'] ?? ''),
            'OS_VERSION' => (string)($matches['OS_VERSION'][0] ?? $configuration['default']['OS_VERSION'] ?? ''),
        ];

        foreach ($configuration['image'] ?? [] as $pattern => $variables) {
            if (!is_array($variables)) {
                throw new RuntimeException(sprintf('conf/console.yml: "dockerTest: configuration: image: %s" has to map settings to values.', $pattern));
            }

            if (preg_match('#' . $pattern . '#i', $name)) {
                foreach ($variables as $key => $value) {
                    $settings[(string)$key] = (string)$value;
                }

                break;
            }
        }

        return $settings;
    }
}
