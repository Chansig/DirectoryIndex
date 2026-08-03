<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex\Tests;

use Chansig\DirectoryIndex\LocaleResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LocaleResolverTest extends TestCase
{
    /** @return iterable<string, array{?string, ?string, string, string}> */
    public static function localeProvider(): iterable
    {
        yield 'forced wins' => ['fr', 'en-US', 'en', 'fr'];
        yield 'missing header' => [null, null, 'en', 'en'];
        yield 'empty header' => [null, '  ', 'fr', 'fr'];
        yield 'regional language' => [null, 'fr-FR, en;q=0.8', 'en', 'fr'];
        yield 'uppercase language' => [null, 'FR-FR, en;q=0.8', 'en', 'fr'];
        yield 'quality order' => [null, 'fr;q=0.2, en;q=0.9', 'fr', 'en'];
        yield 'position breaks tie' => [null, 'fr;q=0.8, en;q=0.8', 'en', 'fr'];
        yield 'zero quality ignored' => [null, 'fr;q=0, en;q=0.5', 'fr', 'en'];
        yield 'sole zero quality ignored' => [null, 'fr;q=0', 'en', 'en'];
        yield 'unsupported fallback' => [null, 'de-DE, *;q=0.5', 'en', 'en'];
        yield 'malformed quality keeps default' => [null, 'fr;q=invalid, en;q=0.8', 'en', 'fr'];
        yield 'quality prefix is rejected' => [null, 'de;xq=0, fr;q=0', 'en', 'en'];
        yield 'quality suffix is rejected' => [null, 'fr;q=0junk', 'en', 'fr'];
    }

    #[DataProvider('localeProvider')]
    public function testLocaleResolution(
        ?string $forced,
        ?string $header,
        string $fallback,
        string $expected,
    ): void {
        self::assertSame($expected, (new LocaleResolver())->resolve($forced, $header, $fallback));
    }
}
