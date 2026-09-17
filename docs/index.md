# Font

Use ALTO Font to inspect font files, convert them for distribution, or reduce
them to the characters your application needs.

Load a face once to read its names, character coverage, measurements, and
outlines. The same face can be written to another supported container or
subsetted without changing the source file.

For example, with Composer's autoloader loaded and a font in your project:

```php
use Alto\Font\Font;

$font = Font::fromFile(__DIR__.'/fonts/Inter-Regular.ttf');
echo null === $font->glyphIdForCodepoint(0x41) ? 'A is missing' : 'A is available';
```

A font containing the capital letter A prints `A is available`. The first
example below shows the complete setup and checks a whole text.

## Introduction

- [Installation](installation.md): requirements and Composer setup.
- [Getting started](getting-started.md): load a font and check its characters.
- [Font concepts](fonts.md): distinguish files, faces, families, and glyphs.

## Inspect fonts

- [Loading files](font-files.md): open a known path or choose a collection face.
- [Metadata](metadata.md): read names, styles, versions, and license information.
- [Find a font](discovery.md): select a family or weight from a directory.
- [Glyph metrics](glyphs.md): inspect character mappings, measurements, and outlines.
- [Variable fonts](variations.md): explore axes and measure glyphs at a chosen weight.

## Convert fonts

- [Convert a file](conversion/index.md): write a TTF, WOFF, or WOFF2 file.
- [Writers](conversion/writers.md): write bytes or extract a collection face.
- [Compression](compression/index.md): choose compression settings.
- [WOFF2](compression/woff2.md): configure WOFF2 compression and decompression.

## Subset fonts

- [Create a subset](subsetting/index.md): keep the text you need and measure the result.
- [Select characters](subsetting/unicode-sets.md): build a selection from text or Unicode ranges.
- [Subset options](subsetting/policies.md): choose size and rendering trade-offs.

## Reference

- [API overview](reference/index.md): find the contracts for reading, writing, and subsetting.
- [Supported formats](formats.md): inputs, outputs, and requirements.
- [Font API](font-data.md): signatures, returned values, and failures when reading a face.
- [Subset API](reference/subsetting.md): character sets, policy defaults, and result fields.
- [OpenType support](reference/opentype-subsetting.md): subsetting support and table-level limits.

## Boundaries

The package supports TrueType outlines in the containers listed under
[Supported formats](formats.md). CFF/CFF2 outlines, text shaping, and raster
rendering are outside its scope. Variable-font views let you inspect selected
coordinates; they do not export a fixed-weight font. Check font licensing
before distributing a converted or subsetted file.

## Package

- [Changelog](https://github.com/altophp/font/blob/main/CHANGELOG.md): user-visible changes by release.
- [Contributing](https://github.com/altophp/font/blob/main/CONTRIBUTING.md): prepare a checkout and validate changes.
- [Support](https://github.com/altophp/font/blob/main/SUPPORT.md): ask a question or report a reproducible problem.
