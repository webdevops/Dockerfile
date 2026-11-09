<?php

namespace Webdevops\Build\ReadmeBanner;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

use function array_diff;
use function array_keys;
use function is_array;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_dir;
use function json_decode;
use function mkdir;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Writes the light and dark banner at the top of README.md.
 * Texts come from conf/readme-banner.yml, everything else from the built images.
 */
final class BannerGenerator
{
    public function __construct(private readonly string $rootPath)
    {
    }

    /**
     * @param list<array{image: string, tag: string, from: ?string}> $images published images and the image of their last FROM line
     * @param list<string> $platforms e.g. linux/amd64
     * @return list<string> the written files, relative to the repository root
     */
    public function generate(array $images, array $platforms): array
    {
        $texts = Yaml::parseFile($this->rootPath . '/conf/readme-banner.yml');
        $table = json_decode(
            file_get_contents($this->rootPath . '/ci/resources/readme-banner/glyphs.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        foreach (GlyphFont::FONTS as $name) {
            if (!isset($table[$name])) {
                throw new RuntimeException(sprintf(
                    'ci/resources/readme-banner/glyphs.json has no font "%s". Build it again with ci/console readme-banner:build-glyphs, see ci/resources/readme-banner/README.md.',
                    $name,
                ));
            }
        }
        $fonts = [];
        foreach ($table as $name => $font) {
            $fonts[$name] = GlyphFont::fromTable($name, $font);
        }

        // README.md shows one file per theme, so each theme needs exactly one
        $files = $texts['files'] ?? null;
        $themes = array_keys(BannerRenderer::THEMES);
        if (!is_array($files) || array_diff($themes, array_keys($files)) !== [] || array_diff(array_keys($files), $themes) !== []) {
            throw new RuntimeException(sprintf(
                'conf/readme-banner.yml needs a file for each of the themes %s under "files".',
                implode(' and ', $themes),
            ));
        }

        $renderer = new BannerRenderer(new ImageMatrix($images, $platforms), $texts, $fonts);
        $written = [];
        foreach ($files as $theme => $file) {
            $path = $this->rootPath . '/' . $file;
            if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0777, true) && !is_dir(dirname($path))) {
                throw new RuntimeException(sprintf('Cannot create the directory of %s.', $file));
            }
            if (file_put_contents($path, $renderer->render($theme)) === false) {
                throw new RuntimeException(sprintf('Cannot write %s.', $file));
            }
            $written[] = $file;
        }
        return $written;
    }
}
