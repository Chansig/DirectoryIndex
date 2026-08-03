<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex\Tests;

use Chansig\DirectoryIndex\Entry;
use Chansig\DirectoryIndex\Sorter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SorterTest extends TestCase
{
    /** @return iterable<string, array{string, list<string>}> */
    public static function sortProvider(): iterable
    {
        yield 'name ascending' => ['ascname', ['folder', 'alpha.txt', 'beta.zip']];
        yield 'name descending' => ['descname', ['folder', 'beta.zip', 'alpha.txt']];
        yield 'type ascending' => ['asctype', ['folder', 'beta.zip', 'alpha.txt']];
        yield 'type descending' => ['desctype', ['folder', 'alpha.txt', 'beta.zip']];
        yield 'size ascending' => ['ascsize', ['folder', 'beta.zip', 'alpha.txt']];
        yield 'size descending' => ['descsize', ['folder', 'alpha.txt', 'beta.zip']];
        yield 'modified ascending' => ['ascmodified', ['folder', 'alpha.txt', 'beta.zip']];
        yield 'modified descending' => ['descmodified', ['folder', 'beta.zip', 'alpha.txt']];
    }

    /** @param list<string> $expected */
    #[DataProvider('sortProvider')]
    public function testSortStrategies(string $key, array $expected): void
    {
        $entries = [
            new Entry('alpha.txt', 'alpha.txt', false, 20, 10, 'txt'),
            new Entry('folder', 'folder', true, null, 30, ''),
            new Entry('beta.zip', 'beta.zip', false, 10, 20, 'zip'),
        ];

        self::assertSame($expected, array_column((new Sorter())->sort($entries, $key), 'name'));
    }

    public function testInvalidKeyUsesDefaultAndEqualValuesUseName(): void
    {
        $sorter = new Sorter();
        $entries = [
            new Entry('z.txt', 'z.txt', false, 10, 10, 'txt'),
            new Entry('a.txt', 'a.txt', false, 10, 10, 'txt'),
        ];

        self::assertSame('ascname', $sorter->normalize('invalid'));
        self::assertSame(Sorter::KEYS, array_map($sorter->normalize(...), Sorter::KEYS));
        self::assertSame(['a.txt', 'z.txt'], array_column($sorter->sort($entries, 'ascsize'), 'name'));
    }
}
