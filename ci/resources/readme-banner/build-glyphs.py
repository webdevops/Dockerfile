"""Writes glyphs.json: outlines, advance widths and kerning of the README banner fonts.

Only needed when the banner gets another font or a character that glyphs.json lacks.
Usage (font files as .ttf, .otf or .woff):

    pip install fonttools uharfbuzz
    python build-glyphs.py glyphs.json \
        mono-bold=JetBrainsMono-Bold.ttf mono-medium=JetBrainsMono-Medium.ttf \
        sans-regular=Inter-Regular.ttf sans-medium=Inter-Medium.ttf sans-semibold=Inter-SemiBold.ttf
"""
import io
import json
import sys

import uharfbuzz as hb
from fontTools.pens.basePen import BasePen
from fontTools.ttLib import TTFont

CHARACTERS = [chr(c) for c in range(0x20, 0x7F)] + list("·–—…‘’“”")

SOURCES = {
    "mono": "JetBrains Mono, SIL Open Font License 1.1, see OFL-JetBrainsMono.txt",
    "sans": "Inter, SIL Open Font License 1.1, see OFL-Inter.txt",
}


def number(value):
    value = round(value, 1)
    return str(int(value)) if value == int(value) else str(value)


class CommandPen(BasePen):
    """Outline as absolute M, L, Q, C and Z commands, y axis pointing up."""

    def __init__(self, glyph_set):
        super().__init__(glyph_set)
        self.parts = []

    def _add(self, command, *points):
        self.parts.append(command + " " + " ".join(number(v) for p in points for v in p))

    def _moveTo(self, pt):
        self._add("M", pt)

    def _lineTo(self, pt):
        self._add("L", pt)

    def _qCurveToOne(self, pt1, pt2):
        self._add("Q", pt1, pt2)

    def _curveToOne(self, pt1, pt2, pt3):
        self._add("C", pt1, pt2, pt3)

    def _closePath(self):
        self.parts.append("Z")

    def _endPath(self):
        pass


def read_font(path):
    font = TTFont(path)
    font.flavor = None
    data = io.BytesIO()
    font.save(data)
    shaper = hb.Font(hb.Face(hb.Blob(data.getvalue())))
    return font, shaper


def shaped_width(shaper, text):
    buf = hb.Buffer()
    buf.add_str(text)
    buf.guess_segment_properties()
    hb.shape(shaper, buf, {"kern": True, "liga": False, "calt": False})
    return sum(p.x_advance for p in buf.glyph_positions)


def build_font(key, path):
    font, shaper = read_font(path)
    cmap = font.getBestCmap()
    glyph_set = font.getGlyphSet()
    glyphs = {}
    for char in CHARACTERS:
        if ord(char) not in cmap:
            raise SystemExit(f"{path} has no glyph for {char!r}")
        name = cmap[ord(char)]
        pen = CommandPen(glyph_set)
        glyph_set[name].draw(pen)
        glyphs[char] = [glyph_set[name].width, " ".join(pen.parts)]
    kerning = {}
    for first in CHARACTERS:
        for second in CHARACTERS:
            kern = shaped_width(shaper, first + second) - glyphs[first][0] - glyphs[second][0]
            if kern:
                kerning[first + second] = kern
    return {
        "source": SOURCES[key.split("-")[0]],
        "unitsPerEm": font["head"].unitsPerEm,
        "glyphs": glyphs,
        "kerning": kerning,
    }


def dump(fonts):
    """JSON with one glyph per line, so changes stay readable in a diff."""
    j = lambda v: json.dumps(v, ensure_ascii=False)
    lines = ["{"]
    for i, (key, font) in enumerate(fonts.items()):
        lines.append(f"  {j(key)}: {{")
        lines.append(f'    "source": {j(font["source"])},')
        lines.append(f'    "unitsPerEm": {font["unitsPerEm"]},')
        lines.append('    "glyphs": {')
        entries = [f"      {j(c)}: {j(g)}" for c, g in font["glyphs"].items()]
        lines.append(",\n".join(entries))
        lines.append("    },")
        kerning = ", ".join(f"{j(p)}: {k}" for p, k in sorted(font["kerning"].items()))
        lines.append(f'    "kerning": {{{kerning}}}')
        lines.append("  }" + ("," if i < len(fonts) - 1 else ""))
    lines.append("}")
    return "\n".join(lines) + "\n"


def main(argv):
    if len(argv) < 3:
        raise SystemExit(__doc__)
    fonts = {}
    for arg in argv[2:]:
        key, path = arg.split("=", 1)
        fonts[key] = build_font(key, path)
    with open(argv[1], "w", encoding="utf-8") as fh:
        fh.write(dump(fonts))


if __name__ == "__main__":
    main(sys.argv)
