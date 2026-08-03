<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex\Tests;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

abstract class FilesystemTestCase extends TestCase
{
    protected string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/directory-index-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->root));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            /** @var SplFileInfo $item */
            foreach ($iterator as $item) {
                if ($item->isLink() || $item->isFile()) {
                    unlink($item->getPathname());
                } else {
                    rmdir($item->getPathname());
                }
            }
            rmdir($this->root);
        }

        parent::tearDown();
    }

    protected function file(string $relativePath, string $content = ''): string
    {
        $path = $this->root . '/' . $relativePath;
        $directory = dirname($path);
        if (!is_dir($directory)) {
            self::assertTrue(mkdir($directory, 0777, true));
        }
        self::assertNotFalse(file_put_contents($path, $content));

        return $path;
    }

    protected function directory(string $relativePath): string
    {
        $path = $this->root . '/' . $relativePath;
        self::assertTrue(mkdir($path, 0777, true));

        return $path;
    }
}
