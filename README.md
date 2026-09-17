# ALTO Font

Inspect, convert, and subset font files from PHP. Read a font's family and
style, find a matching face, or keep only the characters your application uses.

&nbsp; ![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-00B7FF?logoColor=00B7FF&labelColor=050608)
&nbsp; ![CI](https://img.shields.io/github/actions/workflow/status/altophp/font/CI.yml?branch=main&label=Tests&labelColor=050608&color=00B7FF)
&nbsp; [![Packagist](https://img.shields.io/packagist/v/alto/font?label=Packagist&labelColor=050608&color=00B7FF)](https://packagist.org/packages/alto/font)
&nbsp; ![License](https://img.shields.io/github/license/altophp/font?label=License&labelColor=050608&color=00B7FF)
&nbsp; [![GitHub Sponsors](https://img.shields.io/github/sponsors/smnandre?logo=githubsponsors&logoColor=00B7FF&label=%20Sponsor&labelColor=050608&color=00B7FF)](https://github.com/sponsors/smnandre)

## Installation

```sh
composer require alto/font
```

Requires PHP 8.4+, Iconv, and Zlib. Reading WOFF2 also needs the Brotli PHP
extension or executable; [writing WOFF2](docs/compression/woff2.md) requires an
explicit compressor. TTF and WOFF examples need no Brotli dependency.

## Read a font

Save this as `inspect.php`, place your font at `fonts/Inter-Regular.ttf`,
and run `php inspect.php`:

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Alto\Font\Font;

$font = Font::fromFile(__DIR__.'/fonts/Inter-Regular.ttf');

printf("%s %s\n", $font->metadata()->family, $font->metadata()->subfamily);
```

For Inter Regular, this prints `Inter Regular`. See
[Getting started](docs/getting-started.md) to check which characters it contains.

## Create a smaller font for your text

Create an `output` directory first. Run this as a separate script beside your
`vendor` directory. The destination must not already exist.

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Alto\Font\Font;
use Alto\Font\Subset\SubsetOptions;
use Alto\Font\Subset\UnicodeSet;
use Alto\Font\Writer\WoffWriter;

$font = Font::fromFile(__DIR__.'/fonts/Inter-Regular.ttf');
$result = $font->subset(new SubsetOptions(
    UnicodeSet::fromText('ALTO Font 0123456789'),
));

$destination = __DIR__.'/output/inter-subset.woff';
new WoffWriter()->write($result->font, $destination);

printf("Saved inter-subset.woff (%d bytes)\n", filesize($destination));
```

This creates a WOFF file containing the requested characters available in the
source, plus required glyph dependencies. The original file is unchanged.
Use the full text your application needs: later text may contain characters
excluded from this subset. Read [Create a subset](docs/subsetting/index.md) for
missing-character checks, output sizes, and options.

![Source and subset character grids for Inter, with WOFF sizes measured in the same format.](docs/assets/figures/subset-before-after.svg)

The illustrated subset uses the text above and default options. See
[figure sources and reproduction](docs/reference/documentation-figures.md).

## Choose a task

| I want to... | Guide |
| --- | --- |
| Read a font's family, style, or license fields | [Read metadata](docs/metadata.md) |
| Check whether a font contains my characters | [Getting started](docs/getting-started.md) |
| Convert a font to TTF, WOFF, or WOFF2 | [Convert a font](docs/conversion/index.md) |
| Reduce a font to the text I use | [Create a subset](docs/subsetting/index.md) |
| Find a font by family, weight, and style | [Find a font](docs/discovery.md) |
| Inspect variable-font axes and glyph measurements | [Variable fonts](docs/variations.md) |

The [documentation index](docs/index.md) also links the API and advanced guides.

## Supported fonts

ALTO Font supports TrueType outlines in TTF/OpenType, WOFF, and WOFF2 files,
and individual faces from TTC/OTC collections. CFF/CFF2 outlines, color glyphs,
and WOFF2 collections are unsupported. See [Formats](docs/formats.md) for
operation-specific limits.

Variable fonts can retain their axes during conversion and subsetting.
Exporting a fixed weight from a variable font is not supported.
ALTO Font reads and transforms font data; text shaping and rendering are
handled by the application or browser using the output.

## Contributing and support

See [Contributing](CONTRIBUTING.md) for tests and development checks.
[Report an issue](https://github.com/altophp/font/issues) or support development
through [GitHub Sponsors](https://github.com/sponsors/smnandre).

## License

Released by [ALTO PHP](https://altophp.com) under the [MIT License](LICENSE).
