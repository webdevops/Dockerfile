<?php

namespace Webdevops\Build\ReadmeBanner;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

use function array_keys;
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
     * @param list<array{image: string, tag: string, file: string}> $images published images
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
        $fonts = [];
        foreach ($table as $name => $font) {
            $fonts[$name] = GlyphFont::fromTable($name, $font);
        }

        $renderer = new BannerRenderer(new ImageMatrix($images, $platforms), $texts, $fonts);
        $written = [];
        foreach ($texts['files'] as $theme => $file) {
            if (!isset(BannerRenderer::THEMES[$theme])) {
                throw new RuntimeException(sprintf(
                    'conf/readme-banner.yml lists a file for the theme "%s"; the banner has the themes %s.',
                    $theme,
                    implode(' and ', array_keys(BannerRenderer::THEMES)),
                ));
            }
            $path = $this->rootPath . '/' . $file;
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            file_put_contents($path, $renderer->render($theme));
            $written[] = $file;
        }
        return $written;
    }
}
