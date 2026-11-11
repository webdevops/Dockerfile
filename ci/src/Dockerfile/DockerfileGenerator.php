<?php

declare(strict_types=1);

namespace Webdevops\Build\Dockerfile;

use RuntimeException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Webdevops\Build\FinderFile;
use Webdevops\Build\TagDirectories;

use function explode;
use function file_put_contents;
use function implode;
use function sprintf;

/**
 * Renders docker/<image>/<tag>/Dockerfile from the Dockerfile.twig next to it.
 *
 * template/Dockerfile/layout.twig writes the header and includes the Dockerfile.twig, which imports the macros it
 * uses from template/Dockerfile/, e.g. {% import 'docker.twig' as docker %}. Directories that are symbolic links,
 * such as docker/php/8.5, are left out; ImageLinks keeps them up to date.
 */
final readonly class DockerfileGenerator
{
    /**
     * @param string $rootPath the repository root
     */
    public function __construct(private string $rootPath = __DIR__ . '/../../..')
    {
    }

    /**
     * Renders every Dockerfile and updates the links of ImageLinks. A tag directory without Dockerfile.twig is an
     * error: nothing would update its files, but generate:github-ci would still build and publish a Dockerfile in it.
     * The .DS_Store files that the macOS Finder leaves under docker/ are removed first.
     *
     * @param ?callable(string): void $log receives the image and tag of each Dockerfile
     */
    public function generate(?callable $log = null): void
    {
        // a .DS_Store would keep the directory of a removed tag from being empty, and get into a local build
        FinderFile::removeUnder($this->rootPath . '/docker');
        $tagDirectories = new TagDirectories($this->rootPath);
        $withoutTemplate = $tagDirectories->withoutTemplate();
        if ($withoutTemplate !== []) {
            throw new RuntimeException(sprintf(
                'No %s in %s. Add the template, or remove the directory to stop building the tag.',
                TagDirectories::TEMPLATE,
                implode(', ', $withoutTemplate),
            ));
        }

        new ImageLinks($this->rootPath)->update($tagDirectories);

        $loader = new FilesystemLoader($this->rootPath . '/template/Dockerfile');
        $loader->addPath($this->rootPath . '/docker', 'docker');

        $twig = new Environment($loader, ['autoescape' => false, 'strict_variables' => true]);

        foreach ($tagDirectories->tags() as $directory) {
            [, $image, $tag] = explode('/', $directory);
            $dockerfile = $twig->render('layout.twig', [
                'Dockerfile' => ['image' => $image, 'tag' => $tag],
                // docker/<image>/<tag>/Dockerfile.twig is @docker/<image>/<tag>/Dockerfile.twig
                'template' => '@' . $directory . '/' . TagDirectories::TEMPLATE,
            ]);

            if (file_put_contents($this->rootPath . '/' . $directory . '/Dockerfile', $dockerfile) === false) {
                throw new RuntimeException(sprintf('Cannot write %s/Dockerfile.', $directory));
            }

            if ($log !== null) {
                $log(sprintf('webdevops/%s:%s', $image, $tag));
            }
        }
    }
}
