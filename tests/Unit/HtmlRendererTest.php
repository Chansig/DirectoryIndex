<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex\Tests;

use Chansig\DirectoryIndex\Entry;
use Chansig\DirectoryIndex\HtmlRenderer;
use Chansig\DirectoryIndex\PathResolver;
use Chansig\DirectoryIndex\ResolvedDirectory;
use Chansig\DirectoryIndex\Translator;

final class HtmlRendererTest extends FilesystemTestCase
{
    private HtmlRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new HtmlRenderer(
            'body{color:red}',
            '<svg id="sprite"></svg>',
            new Translator(),
            new PathResolver($this->root),
        );
    }

    public function testEmptyRootDirectoryIsRendered(): void
    {
        $html = $this->renderer->directory(
            new ResolvedDirectory($this->root, ''),
            [],
            'en',
            'ascname',
            '/index.php',
        );

        self::assertStringContainsString('<html lang="en">', $html);
        self::assertStringContainsString('<title>Index of /</title>', $html);
        self::assertStringContainsString('This directory is empty.', $html);
        self::assertStringNotContainsString('Parent directory', $html);
        self::assertStringContainsString('<style>body{color:red}</style>', $html);
        self::assertStringContainsString('<svg id="sprite"></svg>', $html);
    }

    public function testEntriesParentLinksIconsAndSizesAreRendered(): void
    {
        $entries = [
            new Entry('folder', 'documents/folder', true, null, 1_700_000_000, ''),
            new Entry('archive.zip', 'documents/archive.zip', false, 0, 1_700_000_000, 'zip'),
            new Entry('image.png', 'documents/image.png', false, 1_024, 1_700_000_000, 'png'),
            new Entry('audio.mp3', 'documents/audio.mp3', false, 1_048_576, 1_700_000_000, 'mp3'),
            new Entry('video.mp4', 'documents/video.mp4', false, 1_073_741_824, 1_700_000_000, 'mp4'),
            new Entry('paper.pdf', 'documents/paper.pdf', false, 1_099_511_627_776, 1_700_000_000, 'pdf'),
            new Entry('raw.bin', 'documents/raw.bin', false, 1_125_899_906_842_624, 1_700_000_000, 'bin'),
            new Entry('huge.bin', 'documents/huge.bin', false, PHP_INT_MAX, 1_700_000_000, 'bin'),
        ];

        $html = $this->renderer->directory(
            new ResolvedDirectory($this->root . '/documents', 'documents'),
            $entries,
            'fr',
            'desctype',
            '/downloads/index.php',
        );

        self::assertStringContainsString('Répertoire parent', $html);
        self::assertStringContainsString('/downloads/index.php?sort=desctype', $html);
        self::assertStringContainsString('/downloads/index.php?path=documents%2Ffolder&amp;sort=desctype', $html);
        self::assertStringContainsString('/downloads/documents/archive.zip', $html);
        self::assertStringContainsString('icon-archive', $html);
        self::assertStringContainsString('icon-image', $html);
        self::assertStringContainsString('icon-audio', $html);
        self::assertStringContainsString('icon-video', $html);
        self::assertStringContainsString('icon-document', $html);
        self::assertStringContainsString('icon-file', $html);
        self::assertStringContainsString('0 o', $html);
        self::assertStringContainsString('1 Kio', $html);
        self::assertStringContainsString('1 Mio', $html);
        self::assertStringContainsString('1 Gio', $html);
        self::assertStringContainsString('1 Tio', $html);
        self::assertStringContainsString('1 Pio', $html);
        self::assertStringContainsString('8192 Pio', $html);
        self::assertStringContainsString('aria-sort="descending"', $html);
        self::assertStringContainsString('icon-sort-down', $html);
        self::assertStringContainsString('<span>folder/</span>', $html);
        self::assertStringContainsString('2023-11-14 22:13:20 UTC', $html);
    }

    public function testHtmlAndUrlsAreEscaped(): void
    {
        $entry = new Entry('"<script>.txt', '"<script>.txt', false, 1_281, 0, 'txt');

        $html = $this->renderer->directory(
            new ResolvedDirectory($this->root, ''),
            [$entry],
            'en',
            'ascname',
            'index.php',
        );

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&quot;&lt;script&gt;.txt', $html);
        self::assertStringContainsString('/%22%3Cscript%3E.txt', $html);
        self::assertStringContainsString('1.25 KiB', $html);
        self::assertStringContainsString('aria-sort="ascending"', $html);
        self::assertStringContainsString('icon-sort-up', $html);
        self::assertStringStartsWith('<!doctype html><html lang="en"><head>', $html);
        self::assertStringContainsString('<meta name="viewport"', $html);
        self::assertStringEndsWith('</body></html>', $html);
    }

    public function testErrorPageEscapesMessage(): void
    {
        $html = $this->renderer->error('en', '<strong>denied</strong>');

        self::assertStringContainsString('Unable to display this directory', $html);
        self::assertStringContainsString('&lt;strong&gt;denied&lt;/strong&gt;', $html);
        self::assertStringNotContainsString('<strong>', $html);
    }
}
