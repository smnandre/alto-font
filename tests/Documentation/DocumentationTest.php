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

namespace Alto\Font\Tests\Documentation;

use Alto\Font\Font;
use Alto\Font\Tests\Fixtures\TinyTrueTypeFont;
use Alto\Font\Writer\SfntWriter;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class DocumentationTest extends TestCase
{
    public function testInternalMarkdownLinksResolve(): void
    {
        foreach (self::documentationFiles() as $file) {
            $markdown = file_get_contents($file);
            self::assertIsString($markdown);
            $matches = [];
            preg_match_all('/\[[^]]+]\((?!https?:|mailto:|#)([^)\s]+\.(?:md|svg))(?:#[^)]+)?\)/', $markdown, $matches);

            foreach ($matches[1] as $target) {
                self::assertFileExists(
                    dirname($file) . '/' . rawurldecode($target),
                    \sprintf('Broken documentation link "%s" in %s.', $target, $file),
                );
            }
        }
    }

    public function testPhpExamplesAreSyntacticallyValidAndReferenceExistingTypes(): void
    {
        foreach (self::documentationFiles() as $file) {
            foreach (self::phpExamples($file) as $index => $code) {
                $imports = [];
                preg_match_all('/^use ([A-Za-z\\\\]+);$/m', $code, $imports);

                foreach ($imports[1] as $type) {
                    self::assertTrue(
                        class_exists($type) || interface_exists($type) || enum_exists($type),
                        \sprintf('Unknown type "%s" in PHP example %d from %s.', $type, $index + 1, $file),
                    );
                }

                $source = str_starts_with(ltrim($code), '<?php') ? $code : "<?php\n" . $code;
                self::assertPhpSyntax($source, $file, $index + 1);
            }
        }
    }

    public function testStandalonePhpExamplesExecuteAgainstTheInstalledApi(): void
    {
        $examples = [
            __DIR__ . '/../../docs/discovery.md' => [4],
            __DIR__ . '/../../docs/subsetting/unicode-sets.md' => [0, 1],
        ];

        foreach ($examples as $file => $indexes) {
            $blocks = self::phpExamples($file);

            foreach ($indexes as $index) {
                self::assertArrayHasKey($index, $blocks);
                self::assertPhpExecution($blocks[$index], $file, $index + 1);
            }
        }
    }

    /**
     * @param list<int> $indexes
     * @param list<string> $outputs
     */
    #[DataProvider('fontWorkflowExamples')]
    public function testFontWorkflowExamplesExecuteAndProduceUsableFiles(string $file, array $indexes, array $outputs, string $expectedOutput, bool $requiresBrotli = false): void
    {
        if ($requiresBrotli && !\function_exists('brotli_compress')) {
            self::markTestSkipped('The documented extension adapter requires ext-brotli.');
        }

        $directory = sys_get_temp_dir() . '/alto-font-doc-workflow-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory, 0700));

        try {
            foreach (['fonts', 'output', 'vendor'] as $child) {
                self::assertTrue(mkdir($directory . '/' . $child, 0700));
            }

            TinyTrueTypeFont::write($directory . '/fonts/Inter-Regular.ttf');
            TinyTrueTypeFont::writeVariableWithHvar($directory . '/fonts/Variable.ttf', includeGvar: true);

            if ('docs/discovery.md' === $file && $requiresBrotli) {
                new SfntWriter()->write(
                    Font::fromFile(__DIR__ . '/../Fixtures/Fonts/Inter-Regular-latin.woff2'),
                    $directory . '/fonts/Inter.ttf',
                );
            }
            TinyTrueTypeFont::writeCollection($directory . '/fonts/Collection.ttc', [1000, 2048]);
            $autoload = var_export(__DIR__ . '/../../vendor/autoload.php', true);
            self::assertIsInt(file_put_contents($directory . '/vendor/autoload.php', '<?php require ' . $autoload . ';'));
            $blocks = self::phpExamples(__DIR__ . '/../../' . $file);
            $code = '<?php require ' . $autoload . ";\n";

            foreach ($indexes as $index) {
                self::assertArrayHasKey($index, $blocks);
                $code .= str_replace('<?php', '', $blocks[$index]) . "\n";
            }

            self::assertIsInt(file_put_contents($directory . '/example.php', $code));
            $process = proc_open([PHP_BINARY, $directory . '/example.php'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $status = proc_close($process);

            self::assertSame(0, $status, \sprintf('Documentation workflow %s failed: %s', $file, $stderr));
            self::assertSame('', $stderr);
            self::assertIsString($stdout);
            self::assertStringContainsString($expectedOutput, $stdout);

            foreach ($outputs as $output) {
                $path = $directory . '/' . $output;
                self::assertFileExists($path);
                $font = Font::fromFile($path);
                self::assertSame('Atelier Tiny', $font->descriptor()->family);
                self::assertNotNull($font->glyphIdForCodepoint(0x41));
                self::assertGreaterThan(0, $font->metrics('A')->advanceWidth);

                if ('output/selected-face.ttf' === $output) {
                    self::assertSame(2048, $font->face()->unitsPerEm);
                    self::assertSame(1, $font->face()->faceCount);
                }

                if (\in_array($output, ['output/inter-subset.woff', 'output/inter-compact.woff'], true)) {
                    self::assertNull($font->glyphIdForCodepoint(0x00C1));
                    self::assertNotNull(Font::fromFile($directory . '/fonts/Inter-Regular.ttf')->glyphIdForCodepoint(0x00C1));
                }
            }

            if ('README.md' === $file && [1] === $indexes) {
                self::assertSame(\sprintf("Saved inter-subset.woff (%d bytes)\n", filesize($directory . '/output/inter-subset.woff')), $stdout);
            }
        } finally {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($iterator as $entry) {
                if ($entry instanceof \SplFileInfo) {
                    $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
                }
            }

            rmdir($directory);
        }
    }

    /**
     * @return iterable<string, array{string, list<int>, list<string>, string, bool}>
     */
    public static function fontWorkflowExamples(): iterable
    {
        yield 'README inspection' => ['README.md', [0], [], "Atelier Tiny Regular\n", false];
        yield 'README complete subset' => ['README.md', [1], ['output/inter-subset.woff'], 'Saved inter-subset.woff', false];
        yield 'overview character lookup' => ['docs/index.md', [0], [], 'A is available', false];
        yield 'installation bootstrap' => ['docs/installation.md', [0], [], 'ALTO Font is ready', false];
        yield 'getting started and character check' => ['docs/getting-started.md', [0, 1], [], 'U+00E9: missing', false];
        yield 'subset file' => ['docs/subsetting/index.md', [0], ['output/inter-subset.woff'], 'requested characters found', false];
        yield 'compact subset file' => ['docs/subsetting/policies.md', [0], ['output/inter-compact.woff'], 'Saved inter-compact.woff', false];
        yield 'convert to WOFF' => ['docs/conversion/index.md', [0], ['output/inter.woff'], 'Saved inter.woff', false];
        yield 'metadata and style' => ['docs/metadata.md', [0, 1], [], 'Weight: 400, style: normal, stretch: 100%', false];
        yield 'glyph lookup and geometry' => ['docs/glyphs.md', [0, 1, 2, 3, 4], [], "Glyph: 1\n", false];
        yield 'variable axes and measurements' => ['docs/variations.md', [0, 1], [], 'wght: 100 to 900, default 400', false];
        yield 'missing font family' => ['docs/discovery.md', [0, 1, 2], [], "Inter was not found in fonts.\n", false];
        yield 'matching font family' => ['docs/discovery.md', [0, 1, 2], [], 'Found Inter Regular', true];
        yield 'write WOFF2 with extension' => ['docs/compression/woff2.md', [0], ['output/inter.woff2'], 'Saved inter.woff2', true];
        yield 'writers and collection extraction' => ['docs/conversion/writers.md', [0, 2], ['output/inter.ttf', 'output/inter.woff', 'output/inter.woff2', 'output/selected-face.ttf'], '', true];
    }

    /**
     * @return list<string>
     */
    private static function documentationFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                __DIR__ . '/../../docs',
                \FilesystemIterator::SKIP_DOTS,
            ),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }

            if ($file->isFile() && 'md' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }

        $files[] = __DIR__ . '/../../README.md';
        $files[] = __DIR__ . '/../../CONTRIBUTING.md';
        $files[] = __DIR__ . '/../../SUPPORT.md';
        sort($files);

        return $files;
    }

    /**
     * @return list<string>
     */
    private static function phpExamples(string $file): array
    {
        $markdown = file_get_contents($file);
        self::assertIsString($markdown);
        $blocks = [];
        preg_match_all('/```php\n(.*?)```/s', $markdown, $blocks);

        return $blocks[1];
    }

    private static function assertPhpSyntax(string $code, string $file, int $example): void
    {
        $path = tempnam(sys_get_temp_dir(), 'alto-font-docs-');
        self::assertIsString($path);

        try {
            self::assertIsInt(file_put_contents($path, $code));
            $process = proc_open(
                [PHP_BINARY, '-l', $path],
                [
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes,
            );
            self::assertIsResource($process);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            self::assertSame(
                0,
                $exitCode,
                \sprintf(
                    "Invalid PHP syntax in example %d from %s.\n%s%s",
                    $example,
                    $file,
                    \is_string($output) ? $output : '',
                    \is_string($error) ? $error : '',
                ),
            );
        } finally {
            @unlink($path);
        }
    }

    private static function assertPhpExecution(string $code, string $file, int $example): void
    {
        $path = tempnam(sys_get_temp_dir(), 'alto-font-docs-exec-');
        self::assertIsString($path);
        $autoload = var_export(__DIR__ . '/../../vendor/autoload.php', true);
        $source = "<?php\nrequire " . $autoload . ";\n" . $code;

        try {
            self::assertIsInt(file_put_contents($path, $source));
            $process = proc_open(
                [PHP_BINARY, $path],
                [
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes,
            );
            self::assertIsResource($process);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            self::assertSame(
                0,
                $exitCode,
                \sprintf(
                    "PHP example %d from %s is not executable.\n%s%s",
                    $example,
                    $file,
                    \is_string($output) ? $output : '',
                    \is_string($error) ? $error : '',
                ),
            );
        } finally {
            @unlink($path);
        }
    }
}
