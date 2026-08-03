<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex\Tests;

use Chansig\DirectoryIndex\Entry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EntryTest extends TestCase
{
    /** @return iterable<string, array{bool, string, string}> */
    public static function typeProvider(): iterable
    {
        yield 'folder' => [true, '', 'folder'];
        $extensions = [
            'archive' => ['7z', 'bz', 'bz2', 'gz', 'rar', 'tar', 'tgz', 'zip'],
            'image' => ['avif', 'bmp', 'gif', 'jpeg', 'jpg', 'png', 'svg', 'tif', 'tiff', 'webp'],
            'audio' => ['aac', 'flac', 'm4a', 'mp3', 'ogg', 'wav'],
            'video' => ['avi', 'm4v', 'mkv', 'mov', 'mp4', 'webm'],
            'document' => ['csv', 'doc', 'docx', 'md', 'odt', 'pdf', 'rtf', 'txt', 'xls', 'xlsx'],
        ];
        foreach ($extensions as $type => $typeExtensions) {
            foreach ($typeExtensions as $extension) {
                yield $extension => [false, $extension, $type];
            }
        }
        yield 'unknown' => [false, 'bin', 'file'];
    }

    #[DataProvider('typeProvider')]
    public function testTypeIsInferred(bool $directory, string $extension, string $expected): void
    {
        $entry = new Entry('name', 'name', $directory, $directory ? null : 10, 1, $extension);

        self::assertSame($expected, $entry->type());
    }
}
