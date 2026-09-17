# Subset API

The public types below belong to `Alto\Font\Subset`. For a complete file-to-file
workflow, start with [Create a subset](../subsetting/index.md). This page records
selection contracts, defaults, result fields, and failures.

## Character selection

`UnicodeSet` is immutable, countable, and iterable. Factories normalize repeated
or overlapping codepoints into ordered ranges.

| Signature | Contract |
| --- | --- |
| `UnicodeSet::fromText(string $text): UnicodeSet` | Select codepoints in valid UTF-8 text. No Unicode normalization or equivalent characters are added. |
| `UnicodeSet::fromCodepoints(iterable $codepoints): UnicodeSet` | Select integer values from U+0000 through U+10FFFF. |
| `UnicodeSet::fromRanges(iterable $ranges): UnicodeSet` | Merge overlapping or adjacent `UnicodeRange` values. |
| `UnicodeSet::fromCss(string $unicodeRange): UnicodeSet` | Parse a non-empty CSS unicode-range list, including supported wildcard notation. |
| `union(UnicodeSet $other): UnicodeSet` | Include values in either set. |
| `intersect(UnicodeSet $other): UnicodeSet` | Include values present in both sets. |
| `without(UnicodeSet $other): UnicodeSet` | Remove values present in the other set. |
| `contains(int $codepoint): bool` | Test whether one valid codepoint belongs to the set. |
| `isEmpty(): bool` | Test whether the set represents no codepoints. |
| `count(): int` | Count distinct represented codepoints, not ranges. Also available through `count($set)`. |
| `toCss(): string` | Serialize the normalized ranges as CSS unicode-range text. |

`UnicodeRange::single(int $codepoint): UnicodeRange` selects one value.
`UnicodeRange::between(int $start, int $end): UnicodeRange` uses inclusive bounds.
Invalid bounds, codepoints, or CSS syntax throw `InvalidUnicodeRangeException`.
Invalid UTF-8 input throws `InvalidTextException`.

Iteration yields individual codepoints. Set operations work on ranges, while
iteration expands them. Selecting an empty text, iterable, or range collection
produces an empty set; an empty CSS string is invalid.

## Subset options

`SubsetOptions` has four readonly constructor properties:

| Parameter | Default | Contract |
| --- | --- | --- |
| `UnicodeSet $unicodes` | Required | Characters requested from the source. Missing characters are not created. |
| `HintingPolicy $hinting` | `Keep` | Preserve hinting, or remove it with `Drop`. |
| `GlyphIdPolicy $glyphIds` | `Preserve` | Keep original glyph slots, or renumber retained glyphs with `Compact`. |
| `LayoutPolicy $layout` | `Preserve` | Preserve supported OpenType layout, keep substitutions with `SubstitutionsOnly`, or remove it with `Drop`. |

These are transformation policies, not guarantees of identical pixels in every
renderer. Legacy kerning is handled independently of OpenType layout. See
[Choose subset options](../subsetting/policies.md) for the practical tradeoffs
and [OpenType support](opentype-subsetting.md) for exact table limitations.

## Subset result

`Font::subset(SubsetOptions $options): SubsetResult` returns these readonly fields:

| Property | Meaning |
| --- | --- |
| `Font $font` | New font to inspect or pass to a writer. |
| `UnicodeSet $requestedUnicodes` | Requested normalized selection, including characters not present in the source. |
| `int $mappedCodepointCount` | Requested codepoints found in the source character map. |
| `int $originalGlyphCount` | Source glyph-slot count. |
| `int $retainedGlyphCount` | Retained glyph count including required dependencies and the missing-glyph placeholder. |
| `int $sfntSize` | Size in bytes of the resulting uncompressed SFNT data. |
| `list<string> $warnings` | Transformation and preservation messages. This is not a list of missing characters. |

Preserve mode leaves unused glyph slots empty. Consequently,
`$result->font->face()->glyphCount` can exceed `retainedGlyphCount`.
Use the written file's size to measure WOFF or WOFF2 output.

## Failures and side effects

Subsetting does not write a file or modify the source face. It may retain glyphs
needed by compound outlines or supported substitutions beyond the requested
character mappings.

`UnsupportedFontException` reports unsupported outlines, selected variable
views, or structures that cannot be safely preserved or rewritten.
`InvalidFontException` reports malformed or inconsistent data and invalid
layout offsets. Both implement `FontExceptionInterface`.

Supported variable fonts retain their axes. Subsetting does not export a static
instance or restrict axis ranges. Use the original source, or call
`withoutVariations()` before subsetting a selected view.
