<?php

declare(strict_types=1);

/*
 * This file is part of the ALTO library.
 *
 * © 2026-present Simon André
 *
 * For full copyright and license information, please see
 * the LICENSE file distributed with this source code.
 */

use Alto\Font\Compression\BrotliExtensionCompressor;
use Alto\Font\Font;
use Alto\Font\Glyph\GlyphOutline;
use Alto\Font\Subset\GlyphIdPolicy;
use Alto\Font\Subset\HintingPolicy;
use Alto\Font\Subset\SubsetOptions;
use Alto\Font\Subset\UnicodeSet;
use Alto\Font\Writer\SfntWriter;
use Alto\Font\Writer\Woff2Writer;
use Alto\Font\Writer\WoffWriter;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Small SVG serializer for documentation, not a public rendering API.
 */
final class DocumentationFigure
{
    private string $body = '';

    public function __construct(
        private readonly string $title,
        private readonly string $description,
        private readonly int $height,
    ) {
        $this->text(32, 45, $title, 28);
    }

    public function text(float $x, float $y, string $text, int $size = 19, string $color = '#243047'): void
    {
        $this->body .= sprintf('<text x="%g" y="%g" font-size="%d" fill="%s">%s</text>', $x, $y, $size, $color, htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8')) . "\n";
    }

    public function line(float $x1, float $y1, float $x2, float $y2, string $color = '#b4c0d2', bool $dashed = false): void
    {
        $this->body .= sprintf('<path d="M %g %g L %g %g" fill="none" stroke="%s" stroke-width="1.5"%s/>', $x1, $y1, $x2, $y2, $color, $dashed ? ' stroke-dasharray="5 5"' : '') . "\n";
    }

    public function rect(float $x, float $y, float $width, float $height, string $color): void
    {
        $this->body .= sprintf('<rect x="%g" y="%g" width="%g" height="%g" rx="4" fill="%s"/>', $x, $y, $width, $height, $color) . "\n";
    }

    public function point(float $x, float $y, bool $control = false): void
    {
        $this->body .= sprintf('<circle cx="%g" cy="%g" r="4" fill="%s" stroke="%s" stroke-width="2"/>', $x, $y, $control ? '#ffffff' : '#2254d7', $control ? '#bc4d16' : '#2254d7') . "\n";
    }

    public function outline(GlyphOutline $outline, string $fill = '#243047', ?string $stroke = null): void
    {
        $commands = [];
        foreach ($outline->contours as $contour) {
            foreach ($contour->commands as $command) {
                $commands[] = $command->type . implode(' ', array_map(static fn(float $value): string => sprintf('%.3f', $value), $command->coordinates));
            }
        }
        $this->body .= '<path d="' . implode(' ', $commands) . '" fill="' . $fill . '"' . (null === $stroke ? '' : ' stroke="' . $stroke . '" stroke-width="1.5"') . "/>\n";
    }

    public function glyph(Font $font, string $character, float $size, float $x, float $baseline, string $color = '#243047'): void
    {
        $outline = $font->glyphOutline($font->metrics($character)->glyphId);
        $scale = $size / $font->face()->unitsPerEm;
        $this->outline($outline->transform($scale, 0, 0, -$scale, $x, $baseline), $color);
    }

    /**
     * Simple glyph advances, deliberately without shaping or kerning.
     */
    public function specimen(Font $font, string $characters, float $size, float $x, float $baseline, string $color = '#243047'): void
    {
        foreach (str_split($characters) as $character) {
            $this->glyph($font, $character, $size, $x, $baseline, $color);
            $x += $font->metrics($character)->advanceWidth * $size / $font->face()->unitsPerEm;
        }
    }

    public function save(string $directory, string $name): void
    {
        $escape = static fn(string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $svg = sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="900" height="%d" viewBox="0 0 900 %d" role="img" aria-labelledby="title description">', $this->height, $this->height)
            . "\n<title id=\"title\">" . $escape($this->title) . '</title>'
            . "\n<desc id=\"description\">" . $escape($this->description) . '</desc>'
            . "\n" . sprintf('<rect width="900" height="%d" fill="#fafbfe"/>', $this->height)
            . "\n<g font-family=\"Arial, Helvetica, sans-serif\">\n" . $this->body . "</g>\n</svg>\n";
        if (false === file_put_contents($directory . '/' . $name . '.svg', $svg)) {
            throw new RuntimeException('Cannot write figure: ' . $name);
        }
    }
}

$root = dirname(__DIR__);
$directory = $argv[1] ?? $root . '/docs/assets/figures';
if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
    throw new RuntimeException('Cannot create output directory.');
}
$interPath = $root . '/tests/Fixtures/Fonts/Inter-Regular-latin.woff2';
$variablePath = $root . '/tests/Fixtures/Fonts/AltoCorpusVariable.ttf';
$inter = Font::fromFile($interPath);
$variable = Font::fromFile($variablePath);
$text = 'ALTO Font 0123456789';
$characters = UnicodeSet::fromText($text);
$subset = $inter->subset(new SubsetOptions($characters));
$compact = $inter->subset(new SubsetOptions($characters, glyphIds: GlyphIdPolicy::Compact));
$unhinted = $inter->subset(new SubsetOptions($characters, glyphIds: GlyphIdPolicy::Compact, hinting: HintingPolicy::Drop));
$writer = new WoffWriter();
$sourceBytes = strlen($writer->dump($inter));
$subsetBytes = strlen($writer->dump($subset->font));
$compactBytes = strlen($writer->dump($compact->font));
$unhintedBytes = strlen($writer->dump($unhinted->font));
$woff2Bytes = strlen(new Woff2Writer(new BrotliExtensionCompressor())->dump($inter));
$sfntBytes = strlen(new SfntWriter()->dump($inter));

$figure = new DocumentationFigure('Read a font', 'Inter Regular glyph outlines, metadata, and sample character availability read through ALTO Font.', 360);
$figure->text(32, 83, 'Inter Regular', 20, '#5d6878');
$figure->specimen($inter, 'AaBb0123', 110, 32, 215);
$figure->text(32, 275, $inter->face()->glyphCount . ' glyphs', 22);
$figure->text(290, 275, $inter->face()->unitsPerEm . ' units per em', 22);
$figure->text(570, 275, 'Source: WOFF2', 22);
$figure->text(32, 324, 'A: ' . (null === $inter->glyphIdForCodepoint(65) ? 'missing' : 'available'), 20, '#2254d7');
$figure->text(290, 324, 'U+65E5: ' . (null === $inter->glyphIdForCodepoint(0x65E5) ? 'missing' : 'available'), 20, '#5d6878');
$figure->save($directory, 'font-specimen');

$figure = new DocumentationFigure('Keep the characters your text needs', 'Inter subset for ALTO Font 0123456789. The sample grid shows A-Z and 0-9; pale crossed cells are no longer mapped in the subset. Both file sizes use WOFF.', 670);
$figure->text(32, 84, 'Text: ' . $text, 20, '#5d6878');
foreach ([$inter, $subset->font] as $column => $font) {
    $left = 32 + 450 * $column;
    $figure->text($left, 132, 0 === $column ? 'Source' : 'Subset', 24);
    foreach (str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') as $index => $character) {
        $x = $left + ($index % 6) * 64;
        $y = 157 + intdiv($index, 6) * 63;
        $present = null !== $font->glyphIdForCodepoint(ord($character));
        $figure->rect($x, $y, 56, 54, $present ? '#e9eefb' : '#f0f2f6');
        $figure->glyph($inter, $character, 32, $x + 15, $y + 39, $present ? '#2254d7' : '#adb6c3');
        if (!$present) {
            $figure->line($x + 6, $y + 48, $x + 50, $y + 6, '#9da7b6');
        }
    }
    $figure->text($left, 574, number_format(0 === $column ? $sourceBytes : $subsetBytes) . ' bytes / WOFF', 24);
}
$figure->text(32, 620, 'Sample grid only. Lowercase letters, spaces and glyph dependencies are not shown.', 18, '#5d6878');
$figure->text(32, 649, 'Same output format; default subset options. Original font unchanged.', 18, '#5d6878');
$figure->save($directory, 'subset-before-after');

$metrics = $inter->metrics('A');
$outline = $inter->glyphOutline($metrics->glyphId);
$origin = 220;
$baseline = 410;
$scale = 0.18;
$end = $origin + $metrics->advanceWidth * $scale;
$inkStart = $origin + $metrics->leftSideBearing * $scale;
$figure = new DocumentationFigure('Glyph width and advance are different', 'Inter Regular A. The baseline is y=0; advance width is the origin-to-next-origin distance. The left side bearing separates the origin from the left edge of this glyph.', 590);
$figure->text(32, 85, 'Inter Regular / A / values in font design units', 20, '#5d6878');
$figure->line(80, $baseline, 820, $baseline, '#2254d7');
$figure->line($origin, 115, $origin, 490, '#2254d7', true);
$figure->line($end, 115, $end, 490, '#2254d7', true);
$figure->line($inkStart, 130, $inkStart, 425, '#bc4d16', true);
$figure->outline($outline->transform($scale, 0, 0, -$scale, $origin, $baseline));
$figure->text(590, 174, 'Advance width', 20, '#2254d7');
$figure->text(590, 210, $metrics->advanceWidth . ' units', 30);
$figure->text(590, 270, 'Left side bearing', 20, '#bc4d16');
$figure->text(590, 306, $metrics->leftSideBearing . ' units', 30);
$figure->text(630, 395, 'Baseline / y = 0', 19, '#2254d7');
$figure->line($origin, 465, $end, 465, '#2254d7');
$figure->line($origin, 457, $origin, 473, '#2254d7');
$figure->line($end, 457, $end, 473, '#2254d7');
$figure->text($origin - 25, 512, 'Origin', 19, '#2254d7');
$figure->text($end - 30, 512, 'Next origin', 19, '#2254d7');
$figure->text(32, 563, 'An advance positions the next glyph. It is not a text-shaping or kerning result.', 19, '#5d6878');
$figure->save($directory, 'glyph-metrics');

$outline = $inter->glyphOutline($inter->metrics('o')->glyphId);
$transformed = $outline->transform(0.28, 0, 0, -0.28, 32, 450);
$figure = new DocumentationFigure('A contour is made of drawing commands', 'Inter Regular o with endpoints and quadratic control points. A magnified first quadratic segment shows the start, control point, and endpoint in font units.', 610);
$figure->text(32, 85, 'Inter Regular / o', 20, '#5d6878');
$figure->outline($transformed, '#e4eafa', '#2254d7');
foreach ($transformed->contours as $contour) {
    $lastX = 0.0;
    $lastY = 0.0;
    foreach ($contour->commands as $command) {
        $coordinates = $command->coordinates;
        if ('Q' === $command->type) {
            [$cx, $cy, $x, $y] = $coordinates;
            $figure->line($lastX, $lastY, $cx, $cy, '#d5b79e', true);
            $figure->line($cx, $cy, $x, $y, '#d5b79e', true);
            $figure->point($cx, $cy, true);
            $figure->point($x, $y);
            [$lastX, $lastY] = [$x, $y];
        } elseif ('M' === $command->type || 'L' === $command->type) {
            [$lastX, $lastY] = $coordinates;
            $figure->point($lastX, $lastY);
        }
    }
}
// Isolate a real quadratic segment instead of drawing an invented Bezier curve.
$firstContour = $outline->contours[0];
$move = $firstContour->commands[0];
$curve = $firstContour->commands[1];
if ('M' !== $move->type || 'Q' !== $curve->type) {
    throw new RuntimeException('The Inter o fixture no longer starts with the documented quadratic segment.');
}
$segment = new GlyphOutline($outline->glyphId, [new Alto\Font\Glyph\Contour([$move, $curve])]);
$zoom = $segment->transform(0.95, 0, 0, -0.95, 240, 285);
$figure->text(470, 140, 'One quadratic segment', 22);
$figure->outline($zoom, 'none', '#2254d7');
[$sx, $sy] = $zoom->contours[0]->commands[0]->coordinates;
[$cx, $cy, $ex, $ey] = $zoom->contours[0]->commands[1]->coordinates;
$figure->line($sx, $sy, $cx, $cy, '#bc4d16', true);
$figure->line($cx, $cy, $ex, $ey, '#bc4d16', true);
$figure->point($sx, $sy);
$figure->point($cx, $cy, true);
$figure->point($ex, $ey);
$figure->text($ex - 50, $ey - 18, 'End', 18, '#2254d7');
$figure->text($sx - 25, $sy - 18, 'Start', 18, '#2254d7');
$figure->text($cx - 32, $cy + 32, 'Control', 18, '#bc4d16');
$figure->text(470, 397, 'M ' . implode(' ', $move->coordinates), 21);
$figure->text(470, 432, 'Q ' . implode(' ', $curve->coordinates), 21);
$figure->point(40, 519);
$figure->text(55, 526, 'Contour endpoint', 19);
$figure->point(470, 519, true);
$figure->text(486, 526, 'Quadratic control point', 19);
$figure->text(32, 578, 'ALTO commands include implied endpoints. Control points need not lie on the curve.', 18, '#5d6878');
$figure->save($directory, 'glyph-contours');

$figure = new DocumentationFigure('One variable font, three weights', 'Glyph outlines Ag from the bounded Recursive fixture at weights 300, 600, and 900. The other axes are held at MONO=0, CASL=0, slnt=0, CRSV=0. These are read-only ALTO views, not exported static fonts.', 460);
$figure->text(32, 85, 'Recursive sample / MONO 0 / CASL 0 / slnt 0 / CRSV 0', 20, '#5d6878');
$advances = [];
foreach ([300, 600, 900] as $index => $weight) {
    $font = $variable->withVariations(['wght' => $weight, 'MONO' => 0, 'CASL' => 0, 'slnt' => 0, 'CRSV' => 0]);
    $left = 32 + 295 * $index;
    $figure->text($left, 145, 'wght ' . $weight, 24, '#2254d7');
    $figure->line($left, 295, $left + 245, 295);
    $figure->specimen($font, 'Ag', 155, $left, 295);
    $advance = $font->metrics('A')->advanceWidth;
    $advances[(string) $weight] = $advance;
    $figure->text($left, 370, 'A advance: ' . $advance, 20);
}
$figure->text(32, 425, 'Read-only measurements and outlines. This does not create fixed-weight font files.', 18, '#5d6878');
$figure->save($directory, 'variable-weights');

$figure = new DocumentationFigure('Change the format, keep the characters', 'The same complete Inter face is written as TTF, WOFF, and WOFF2. Sizes are measured from ALTO writer output, without subsetting.', 490);
$figure->text(32, 85, 'One complete Inter face / no subsetting', 20, '#5d6878');
$figure->rect(32, 179, 260, 156, '#e9eefb');
$figure->text(55, 224, 'Loaded font', 24);
$figure->specimen($inter, 'Aa', 72, 55, 302);
$figure->line(292, 258, 366, 258, '#2254d7');
$figure->line(366, 145, 366, 365, '#2254d7');
foreach (['TTF' => $sfntBytes, 'WOFF' => $sourceBytes, 'WOFF2' => $woff2Bytes] as $index => $bytes) {
    $y = match ($index) {
        'TTF' => 145, 'WOFF' => 255, default => 365,
    };
    $figure->line(366, $y, 423, $y, '#2254d7');
    $figure->line(413, $y - 6, 423, $y, '#2254d7');
    $figure->line(413, $y + 6, 423, $y, '#2254d7');
    $figure->text(450, $y - 8, $index, 25);
    $figure->text(600, $y - 8, number_format($bytes) . ' bytes', 24, '#2254d7');
    $figure->text(450, $y + 24, match ($index) {
        'TTF' => 'SfntWriter / no container compression', 'WOFF' => 'WoffWriter / automatic Zlib', default => 'Woff2Writer / Brotli Maximum',
    }, 18, '#5d6878');
}
$figure->text(32, 460, 'Measured for this fixture. Ratios depend on the font and compression settings.', 18, '#5d6878');
$figure->save($directory, 'conversion');

$figure = new DocumentationFigure('Compare subset options on the same font', 'All rows select ALTO Font 0123456789 from Inter and preserve layout. WOFF sizes, retained glyph counts and glyph slots are measured, not estimates.', 520);
$figure->text(32, 85, 'Same text, same WOFF writer, layout preserved in every row', 20, '#5d6878');
$rows = [
    ['Defaults', $subset, $subsetBytes],
    ['Compact glyph IDs', $compact, $compactBytes],
    ['Compact + drop hinting', $unhinted, $unhintedBytes],
];
foreach ($rows as $index => [$label, $result, $bytes]) {
    $y = 145 + $index * 105;
    $figure->text(32, $y, $label, 22);
    $figure->text(440, $y, $result->retainedGlyphCount . ' retained / ' . $result->font->face()->glyphCount . ' slots', 19, '#5d6878');
    $figure->text(705, $y, number_format($bytes) . ' bytes', 21, '#2254d7');
    $figure->rect(32, $y + 20, 800 * $bytes / max($subsetBytes, $compactBytes, $unhintedBytes), 17, '#2254d7');
}
$figure->text(32, 453, 'Preserve mode leaves empty glyph slots. Compact mode renumbers glyphs.', 19, '#5d6878');
$figure->text(32, 486, 'Smaller is not always preferable: compare actual rendering before dropping hinting.', 18, '#5d6878');
$figure->save($directory, 'subset-options');

$manifest = [
    'sources' => [
        'Inter-Regular-latin.woff2' => hash_file('sha256', $interPath),
        'AltoCorpusVariable.ttf' => hash_file('sha256', $variablePath),
    ],
    'runtime' => ['php' => PHP_VERSION, 'zlib' => ZLIB_VERSION, 'brotli' => phpversion('brotli')],
    'text' => $text,
    'source_glyphs' => $inter->face()->glyphCount,
    'glyph_A' => ['advance' => $metrics->advanceWidth, 'left_side_bearing' => $metrics->leftSideBearing, 'units_per_em' => $inter->face()->unitsPerEm],
    'conversion_bytes' => ['ttf' => $sfntBytes, 'woff' => $sourceBytes, 'woff2' => $woff2Bytes],
    'subset_woff_bytes' => ['preserve' => $subsetBytes, 'compact' => $compactBytes, 'compact_without_hinting' => $unhintedBytes],
    'retained_glyphs' => $subset->retainedGlyphCount,
    'variable_A_advances' => $advances,
];
file_put_contents($directory . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
printf("Generated 7 figures in %s\n", $directory);
