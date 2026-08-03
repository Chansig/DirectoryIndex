<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex\Tests;

use Chansig\DirectoryIndex\DirectoryIndexException;
use Chansig\DirectoryIndex\PathResolver;
use PHPUnit\Framework\Attributes\DataProvider;

final class PathResolverTest extends FilesystemTestCase
{
    public function testRootAndChildAreResolved(): void
    {
        $child = $this->directory('documents/reports');
        $resolver = new PathResolver($this->root . '/');

        $root = $resolver->resolve('');
        $resolvedChild = $resolver->resolve('/documents/reports/');

        self::assertSame(realpath($this->root), $root->absolutePath);
        self::assertSame('', $root->relativePath);
        self::assertSame(realpath($child), $resolvedChild->absolutePath);
        self::assertSame('documents/reports', $resolvedChild->relativePath);
        self::assertTrue($resolver->contains($child));
        self::assertFalse($resolver->contains($this->root . '-other'));
        self::assertSame('documents/report.txt', $resolver->child('documents', 'report.txt'));
        self::assertSame('report.txt', $resolver->child('', 'report.txt'));
        self::assertSame('documents', $resolver->parent('documents/reports'));
        self::assertSame('', $resolver->parent('documents'));
        self::assertSame('', $resolver->parent(''));
    }

    public function testInvalidRootIsRejected(): void
    {
        $this->expectException(DirectoryIndexException::class);
        $this->expectExceptionMessage('configured root');

        new PathResolver($this->root . '/missing');
    }

    /** @return iterable<string, array{string, int}> */
    public static function invalidPathProvider(): iterable
    {
        yield 'null byte' => ["folder\0name", 400];
        yield 'backslash' => ['folder\\name', 400];
        yield 'current segment' => ['folder/./name', 400];
        yield 'parent segment' => ['folder/../name', 400];
        yield 'empty segment' => ['folder//name', 400];
        yield 'missing' => ['missing', 404];
    }

    #[DataProvider('invalidPathProvider')]
    public function testInvalidPathIsRejected(string $path, int $status): void
    {
        $resolver = new PathResolver($this->root);

        try {
            $resolver->resolve($path);
            self::fail('Expected path resolution to fail.');
        } catch (DirectoryIndexException $exception) {
            self::assertSame($status, $exception->status);
        }
    }

    public function testSymbolicLinkPolicyIsEnforced(): void
    {
        $this->directory('target');
        self::assertTrue(symlink($this->root . '/target', $this->root . '/link'));

        try {
            (new PathResolver($this->root))->resolve('link');
            self::fail('Expected symbolic link to be rejected.');
        } catch (DirectoryIndexException $exception) {
            self::assertSame(403, $exception->status);
        }

        $resolved = (new PathResolver($this->root, true))->resolve('link');
        self::assertSame('target', $resolved->relativePath);
    }

    public function testSymbolicLinkCannotEscapeRoot(): void
    {
        $outside = sys_get_temp_dir();
        self::assertTrue(symlink($outside, $this->root . '/outside'));

        try {
            (new PathResolver($this->root, true))->resolve('outside');
            self::fail('Expected escaping symbolic link to be rejected.');
        } catch (DirectoryIndexException $exception) {
            self::assertSame(403, $exception->status);
        }
    }
}
