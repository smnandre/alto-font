# Documentation figures

These SVGs show real outlines and measurements from the repository's font
fixtures. They are static illustrations, usable in Markdown without JavaScript
or a downloaded webfont.

## Sources

| Font | Figures | License |
| --- | --- | --- |
| `tests/Fixtures/Fonts/Inter-Regular-latin.woff2` | Specimen, subset, metrics, contours, conversion, options | [Inter OFL](https://github.com/altophp/font/blob/main/tests/Fixtures/Fonts/LICENSE-INTER.txt) |
| `tests/Fixtures/Fonts/AltoCorpusVariable.ttf` | Variable weights | [Recursive OFL](https://github.com/altophp/font/blob/main/tests/Fixtures/Fonts/LICENSE-RECURSIVE.txt) |

See [fixture provenance](https://github.com/altophp/font/blob/main/tests/Fixtures/Fonts/README.md) for the
bounded Recursive derivative. `docs/assets/figures/manifest.json` records source SHA-256 hashes,
runtime versions, output byte counts, and the displayed glyph metrics.
The figures contain selected glyph outlines, not embedded font files.

## Generate

From the repository root, with Composer dependencies and the PHP Brotli
extension installed:

```sh
php tools/generate-doc-figures.php
```

An optional directory argument writes a comparison set elsewhere:

```sh
php tools/generate-doc-figures.php /tmp/alto-font-figures
```

The script overwrites its seven named SVGs and manifest in that directory.
It downloads nothing and does not modify font fixtures. Regenerating with the
same fonts and runtime produces identical files. Different Zlib or Brotli
versions may change compressed sizes; update the measured captions in
`docs/subsetting/index.md`, `docs/conversion/index.md`, and `docs/subsetting/policies.md`
when those measurements change.

## What the figures mean

- All outlines come from ALTO's `glyphOutline()`. A small documentation-only
  serializer turns its commands into SVG paths; the library does not export SVG.
- Multi-glyph specimens use individual advances, without shaping or kerning.
  They demonstrate font data, not a complete text-rendering engine.
- The subset text is `ALTO Font 0123456789`. The grid shows character mappings
  for `A-Z` and `0-9` only. Crossed cells are absent from the output character
  map; required glyph dependencies may still be retained internally.
- Source/subset comparisons use the same WOFF writer. Conversion compares the
  full face across TTF, WOFF, and WOFF2. Brotli uses the default Maximum profile.
- Subset options preserve layout in all rows. The rows differ only in compact
  numbering and hint removal; the bars start at zero.
- Variable examples use `wght` 300, 600, and 900, with `MONO=0`, `CASL=0`,
  `slnt=0`, and `CRSV=0`. They are selected views, not static exports.
- Contour markers show ALTO commands, including implied endpoints. They do not
  claim to show only the points explicitly stored in the source font.

Each SVG includes a title and description. The guides provide alternative
text and captions; subset removal is marked with a cross as well as color.
