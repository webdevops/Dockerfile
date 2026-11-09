# README banner fonts

GitHub does not load web fonts in SVG images, so `ci/console github:generate-ci` draws every text of the
README banner as outlines. `glyphs.json` holds the outlines, widths and kerning of the characters it can use,
taken from [JetBrains Mono](https://github.com/JetBrains/JetBrainsMono) and [Inter](https://github.com/rsms/inter).
Both fonts are licensed under the SIL Open Font License 1.1 (`OFL-JetBrainsMono.txt`, `OFL-Inter.txt`),
and so is `glyphs.json`.

`glyphs.json` covers printable ASCII and `·–—…‘’“”`. Regenerate it only to change a font or to add characters
(add them to `EXTRA_CHARACTERS` in `ci/src/ReadmeBanner/Glyphs/GlyphTableBuilder.php` first):

1. Download the fonts as `.ttf` from Google Fonts into `ci/var/fonts/`, which Git ignores. For a client that is not
   a browser, such as curl, the CSS API lists one `.ttf` URL per font and weight:

   ```
   curl -s 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500;700'
   ```

   Save them as `Inter-Regular.ttf`, `Inter-Medium.ttf`, `Inter-SemiBold.ttf`, `JetBrainsMono-Medium.ttf` and
   `JetBrainsMono-Bold.ttf`. The current `glyphs.json` was built from Inter v20 and JetBrains Mono v24, the
   versions in the URLs.

2. Write `glyphs.json` and render the banner again:

   ```
   docker run --rm -ti -v $PWD:/app -w /app webdevops/php:8.4-alpine ci/console readme-banner:build-glyphs \
       mono-bold=ci/var/fonts/JetBrainsMono-Bold.ttf mono-medium=ci/var/fonts/JetBrainsMono-Medium.ttf \
       sans-regular=ci/var/fonts/Inter-Regular.ttf sans-medium=ci/var/fonts/Inter-Medium.ttf \
       sans-semibold=ci/var/fonts/Inter-SemiBold.ttf
   docker run --rm -ti -v $PWD:/app -w /app webdevops/php:8.4-alpine ci/console github:generate-ci
   ```

`readme-banner:build-glyphs` reads fonts with TrueType outlines (`.ttf`) and applies their GPOS kerning the way a
text shaper does. For files it cannot read, such as `.otf` or `.woff2`, it stops and says why.
