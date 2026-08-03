<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex\Tests;

use Chansig\DirectoryIndex\Config;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new Config();

        self::assertNull($config->forcedLocale);
        self::assertSame('en', $config->fallbackLocale);
        self::assertFalse($config->showHidden);
        self::assertFalse($config->followSymlinks);
        self::assertContains('.env', $config->blockedNames);
        self::assertContains('pem', $config->blockedExtensions);
    }

    public function testConfigurationCanBeOverridden(): void
    {
        $config = Config::fromArray([
            'forced_locale' => 'fr',
            'fallback_locale' => 'fr',
            'show_hidden' => true,
            'follow_symlinks' => true,
            'blocked_names' => ['secret', 'credentials'],
            'blocked_extensions' => ['private', 'token'],
        ]);

        self::assertSame('fr', $config->forcedLocale);
        self::assertSame('fr', $config->fallbackLocale);
        self::assertTrue($config->showHidden);
        self::assertTrue($config->followSymlinks);
        self::assertSame(['secret', 'credentials'], $config->blockedNames);
        self::assertSame(['private', 'token'], $config->blockedExtensions);
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidConfigurationProvider(): iterable
    {
        yield 'forced locale type' => [['forced_locale' => false], 'forced_locale must be a string or null.'];
        yield 'forced locale value' => [['forced_locale' => 'de'], 'Supported locales are "en" and "fr".'];
        yield 'fallback locale type' => [['fallback_locale' => 1], 'fallback_locale must be a string.'];
        yield 'fallback locale value' => [['fallback_locale' => 'de'], 'Supported locales are "en" and "fr".'];
        yield 'show hidden' => [['show_hidden' => 1], 'show_hidden must be a boolean.'];
        yield 'follow symlinks' => [['follow_symlinks' => 1], 'follow_symlinks must be a boolean.'];
        yield 'blocked names is not an array' => [
            ['blocked_names' => 'secret'],
            'blocked_names must be a list of strings.',
        ];
        yield 'blocked names is not a list' => [
            ['blocked_names' => ['name' => 'secret']],
            'blocked_names must be a list of strings.',
        ];
        yield 'blocked names contains empty string' => [
            ['blocked_names' => ['']],
            'blocked_names must be a list of non-empty strings.',
        ];
        yield 'blocked extensions contains integer' => [
            ['blocked_extensions' => [1]],
            'blocked_extensions must be a list of non-empty strings.',
        ];
    }

    /** @param array<string, mixed> $values */
    #[DataProvider('invalidConfigurationProvider')]
    public function testInvalidConfigurationIsRejected(array $values, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        Config::fromArray($values);
    }
}
