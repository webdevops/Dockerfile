<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * The PHP and PHP-FPM variables in the environment container, see docs/environment-variables.md#php and #php-fpm.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait PhpEnvironmentChecks
{
    public function testPhpSettingsFromTheEnvironment(): void
    {
        $settings = [
            'date.timezone' => 'PHP_DATE_TIMEZONE',
            'display_errors' => 'PHP_DISPLAY_ERRORS',
            'memory_limit' => 'PHP_MEMORY_LIMIT',
            'post_max_size' => 'PHP_POST_MAX_SIZE',
            'upload_max_filesize' => 'PHP_UPLOAD_MAX_FILESIZE',
            'opcache.memory_consumption' => 'PHP_OPCACHE_MEMORY_CONSUMPTION',
            'opcache.max_accelerated_files' => 'PHP_OPCACHE_MAX_ACCELERATED_FILES',
            'opcache.validate_timestamps' => 'PHP_OPCACHE_VALIDATE_TIMESTAMPS',
            'opcache.revalidate_freq' => 'PHP_OPCACHE_REVALIDATE_FREQ',
            'opcache.interned_strings_buffer' => 'PHP_OPCACHE_INTERNED_STRINGS_BUFFER',
            'sendmail_path' => 'PHP_SENDMAIL_PATH',
            'max_input_vars' => 'php.max_input_vars',
        ];
        $expected = array_map(static fn (string $variable): string => static::ENVIRONMENT[$variable], $settings);
        // the PHP CLI always runs without a time limit
        $expected['max_execution_time'] = '0';

        self::assertSame($expected, $this->phpIniValues(array_keys($expected)));
    }

    public function testPhpFpmSettingsFromTheEnvironment(): void
    {
        $configuration = $this->phpFpmConfiguration();

        self::assertSame([
            'log_limit' => static::ENVIRONMENT['fpm.global.log_limit'],
            'process.max' => static::ENVIRONMENT['FPM_PROCESS_MAX'],
        ], $this->sortedByKey(array_intersect_key($configuration['global'] ?? [], ['process.max' => true, 'log_limit' => true])));
        $expected = $this->sortedByKey([
            'pm.max_children' => static::ENVIRONMENT['FPM_PM_MAX_CHILDREN'],
            'pm.start_servers' => static::ENVIRONMENT['FPM_PM_START_SERVERS'],
            'pm.min_spare_servers' => static::ENVIRONMENT['FPM_PM_MIN_SPARE_SERVERS'],
            'pm.max_spare_servers' => static::ENVIRONMENT['FPM_PM_MAX_SPARE_SERVERS'],
            // PHP-FPM reports seconds without the unit
            'pm.process_idle_timeout' => '15',
            'pm.max_requests' => static::ENVIRONMENT['FPM_MAX_REQUESTS'],
            'ping.path' => static::ENVIRONMENT['fpm.pool.ping.path'],
            'request_terminate_timeout' => static::ENVIRONMENT['FPM_REQUEST_TERMINATE_TIMEOUT'] . 's',
            'rlimit_files' => static::ENVIRONMENT['FPM_RLIMIT_FILES'],
            'rlimit_core' => static::ENVIRONMENT['FPM_RLIMIT_CORE'],
            'php_admin_value[sendmail_path]' => static::ENVIRONMENT['PHP_SENDMAIL_PATH'],
        ]);
        self::assertSame($expected, $this->sortedByKey(array_intersect_key($configuration['www'] ?? [], $expected)));
    }

    /**
     * @param array<string, string> $values
     * @return array<string, string>
     */
    private function sortedByKey(array $values): array
    {
        ksort($values);
        return $values;
    }

    public function testPhpDismodDisablesExtensionsOfTheCliAndPhpFpm(): void
    {
        $container = $this->environmentContainer();
        foreach (['php -m', 'php-fpm -m'] as $command) {
            $modules = $this->lines($this->stdout($command, $container));
            foreach (['redis', 'pgsql', 'pdo_pgsql'] as $module) {
                self::assertNotContains($module, $modules, sprintf('PHP_DISMOD=%s should disable %s in %s', static::ENVIRONMENT['PHP_DISMOD'], $module, $command));
            }

            self::assertContains('apcu', $modules, 'the other extensions stay');
        }
    }

    public function testComposerVersionFromTheEnvironment(): void
    {
        self::assertMatchesRegularExpression('/^Composer version 1\.\d+\.\d+ /m', $this->stdout('composer --version 2>/dev/null', $this->environmentContainer()));
    }

    /**
     * The values of ini_get() in the PHP CLI of the environment container.
     *
     * @param list<string> $names
     * @return array<string, string|false>
     */
    private function phpIniValues(array $names): array
    {
        $script = sprintf('echo json_encode(array_combine(%1$s, array_map(ini_get(...), %1$s)));', var_export($names, true));
        $values = json_decode($this->stdout('php -r ' . escapeshellarg($script), $this->environmentContainer()), true);
        self::assertIsArray($values);
        /** @var array<string, string|false> $values */
        return $values;
    }

    /**
     * The configuration that "php-fpm -tt" reports in the environment container: section => directive => value.
     *
     * @return array<string, array<string, string>>
     */
    private function phpFpmConfiguration(): array
    {
        $result = $this->execute('php-fpm -tt 2>&1', $this->environmentContainer());
        self::assertSame(0, $result->exitCode, $result->describe());

        $configuration = [];
        $section = '';
        foreach (explode("\n", $result->stdout) as $line) {
            if (preg_match('/NOTICE: (?:pid \d+, \w+\(\), line \d+: )?\[([^\]]+)\]$/', $line, $matches)) {
                $section = $matches[1];
            } elseif (preg_match('/NOTICE: (?:pid \d+, \w+\(\), line \d+: )?\t(\S+) = (.*)$/', $line, $matches)) {
                $configuration[$section][$matches[1]] = $matches[2];
            }
        }

        return $configuration;
    }
}
