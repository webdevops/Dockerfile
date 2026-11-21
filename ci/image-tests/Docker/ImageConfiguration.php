<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Docker;

use RuntimeException;
use Symfony\Component\Process\Process;

use function array_is_list;
use function array_keys;
use function array_map;
use function explode;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;
use function strval;
use function trim;

use const JSON_THROW_ON_ERROR;

/**
 * What the image itself sets for its containers, as docker image inspect shows it: the environment variables, the
 * entrypoint, the command and the exposed ports.
 */
final readonly class ImageConfiguration
{
    /**
     * Seconds docker image inspect may take.
     */
    private const int TIMEOUT = 60;

    /**
     * @param array<string, string> $environment the variables of ENV and of the images it builds on, by name
     * @param list<string>|null $entrypoint null if the image has none
     * @param list<string>|null $command null if the image has none
     * @param list<string> $exposedPorts e.g. 80/tcp
     */
    public function __construct(
        public array $environment,
        public ?array $entrypoint,
        public ?array $command,
        public array $exposedPorts,
    ) {
    }

    public static function inspect(string $image): self
    {
        $process = new Process(['docker', 'image', 'inspect', '--format', '{{json .Config}}', $image]);
        $process->setTimeout(self::TIMEOUT);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf('docker image inspect %s failed: %s', $image, trim($process->getErrorOutput())));
        }

        return self::parse($process->getOutput());
    }

    /**
     * @param string $json the output of docker image inspect --format '{{json .Config}}'
     */
    public static function parse(string $json): self
    {
        $configuration = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($configuration)) {
            throw new RuntimeException('docker image inspect returned no configuration: ' . trim($json));
        }

        $environment = [];
        foreach (self::strings($configuration['Env'] ?? null) ?? [] as $variable) {
            [$name, $value] = explode('=', $variable, 2) + [1 => ''];
            $environment[$name] = $value;
        }

        $exposedPorts = $configuration['ExposedPorts'] ?? null;

        return new self(
            $environment,
            self::strings($configuration['Entrypoint'] ?? null),
            self::strings($configuration['Cmd'] ?? null),
            is_array($exposedPorts) ? array_map(strval(...), array_keys($exposedPorts)) : [],
        );
    }

    /**
     * The values of the variables $names, null for a variable that the image does not set.
     *
     * @param list<string> $names
     * @return array<string, string|null>
     */
    public function variables(array $names): array
    {
        $variables = [];
        foreach ($names as $name) {
            $variables[$name] = $this->environment[$name] ?? null;
        }

        return $variables;
    }

    /**
     * @return list<string>|null
     */
    private static function strings(mixed $value): ?array
    {
        if (!is_array($value) || !array_is_list($value)) {
            return null;
        }

        return array_map(static fn (mixed $item): string => is_string($item) ? $item : '', $value);
    }
}
