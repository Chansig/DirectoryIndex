<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex\Tests;

use Chansig\DirectoryIndex\Config;
use Chansig\DirectoryIndex\DirectoryBrowser;
use Chansig\DirectoryIndex\DirectoryIndexException;
use Chansig\DirectoryIndex\PathResolver;

final class DirectoryBrowserTest extends FilesystemTestCase
{
    public function testEntriesAreDescribedAndSensitiveFilesAreExcluded(): void
    {
        $this->file('index.php', 'script');
        $this->file('visible.txt', 'hello');
        $this->file('.hidden.txt', 'hidden');
        $this->file('.env.local', 'secret');
        $this->file('certificate.PEM', 'secret');
        $this->directory('documents');
        touch($this->root . '/visible.txt', 1_700_000_000);
        $paths = new PathResolver($this->root);
        $browser = new DirectoryBrowser(new Config(), $paths, 'index.php');

        $entries = $browser->entries($paths->resolve(''));
        $entriesByName = array_column($entries, null, 'name');
        ksort($entriesByName);

        self::assertCount(2, $entries);
        self::assertSame(['documents', 'visible.txt'], array_keys($entriesByName));
        self::assertSame(5, $entriesByName['visible.txt']->size);
        self::assertSame(1_700_000_000, $entriesByName['visible.txt']->modifiedAt);
        self::assertSame('txt', $entriesByName['visible.txt']->extension);
        self::assertNull($entriesByName['documents']->size);
        self::assertSame('', $entriesByName['documents']->extension);
    }

    public function testHiddenFilesCanBeShownButBlockedNamesRemainHidden(): void
    {
        $this->file('.visible', 'shown');
        $this->file('.env', 'secret');
        $paths = new PathResolver($this->root);
        $browser = new DirectoryBrowser(new Config(showHidden: true), $paths, 'index.php');

        $entries = $browser->entries($paths->resolve(''));

        self::assertSame(['.visible'], array_column($entries, 'name'));
    }

    public function testScriptNameIsOnlyExcludedAtRoot(): void
    {
        $this->file('index.php', 'root');
        $this->file('child/index.php', 'child');
        $paths = new PathResolver($this->root);
        $browser = new DirectoryBrowser(new Config(), $paths, 'index.php');

        self::assertSame(['child'], array_column($browser->entries($paths->resolve('')), 'name'));
        self::assertSame(['index.php'], array_column($browser->entries($paths->resolve('child')), 'name'));
    }

    public function testSymbolicLinksAreFilteredAccordingToPolicy(): void
    {
        $this->file('target.txt', 'target');
        self::assertTrue(symlink($this->root . '/target.txt', $this->root . '/inside-link.txt'));
        self::assertTrue(symlink('/missing-target', $this->root . '/broken-link.txt'));
        self::assertTrue(symlink('/etc/hosts', $this->root . '/outside-link.txt'));

        $disabledPaths = new PathResolver($this->root);
        $disabled = new DirectoryBrowser(new Config(), $disabledPaths, 'index.php');
        self::assertSame(['target.txt'], array_column($disabled->entries($disabledPaths->resolve('')), 'name'));

        $enabledPaths = new PathResolver($this->root, true);
        $enabled = new DirectoryBrowser(new Config(followSymlinks: true), $enabledPaths, 'index.php');
        $enabledNames = array_column($enabled->entries($enabledPaths->resolve('')), 'name');
        sort($enabledNames);
        self::assertSame(
            ['inside-link.txt', 'target.txt'],
            $enabledNames,
        );
    }

    public function testUnreadableDirectoryBecomesControlledError(): void
    {
        $paths = new PathResolver($this->root);
        $browser = new DirectoryBrowser(new Config(), $paths, 'index.php');

        $this->expectException(DirectoryIndexException::class);
        $this->expectExceptionMessage('cannot be read');

        $browser->entries(new \Chansig\DirectoryIndex\ResolvedDirectory($this->root . '/missing', 'missing'));
    }
}
