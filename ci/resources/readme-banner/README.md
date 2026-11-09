# README banner fonts

GitHub does not load web fonts in SVG images, so `ci/console github:generate-ci` draws every text of the
README banner as outlines. `glyphs.json` holds the outlines, widths and kerning of the characters it can use,
taken from [JetBrains Mono](https://github.com/JetBrains/JetBrainsMono) and [Inter](https://github.com/rsms/inter).
Both fonts are licensed under the SIL Open Font License 1.1 (`OFL-JetBrainsMono.txt`, `OFL-Inter.txt`),
and so is `glyphs.json`.

`glyphs.json` covers printable ASCII and `·–—…‘’“”`. Regenerate it with `build-glyphs.py` only to change a
font or to add characters; its docstring shows how.
