<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex;

use InvalidArgumentException;

final readonly class Config
{
    /**
     * @param list<string> $blockedNames
     * @param list<string> $blockedExtensions
     */
    public function __construct(
        public ?string $forcedLocale = null,
        public string $fallbackLocale = 'en',
        public bool $showHidden = false,
        public bool $followSymlinks = false,
        public array $blockedNames = [
            '.env',
            '.git',
            '.hg',
            '.svn',
            '.ssh',
            '.htaccess',
            '.htpasswd',
            'directory-index.config.php',
        ],
        public array $blockedExtensions = ['key', 'pem', 'p12', 'pfx'],
    ) {
        self::assertLocale($this->forcedLocale, true);
        self::assertLocale($this->fallbackLocale, false);
        self::assertStringList($this->blockedNames, 'blockedNames');
        self::assertStringList($this->blockedExtensions, 'blockedExtensions');
    }

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values): self
    {
        $defaults = new self();

        return new self(
            forcedLocale: self::nullableString($values, 'forced_locale', $defaults->forcedLocale),
            fallbackLocale: self::string($values, 'fallback_locale', $defaults->fallbackLocale),
            showHidden: self::boolean($values, 'show_hidden', $defaults->showHidden),
            followSymlinks: self::boolean($values, 'follow_symlinks', $defaults->followSymlinks),
            blockedNames: self::stringList($values, 'blocked_names', $defaults->blockedNames),
            blockedExtensions: self::stringList(
                $values,
                'blocked_extensions',
                $defaults->blockedExtensions,
            ),
        );
    }

    private static function assertLocale(?string $locale, bool $nullable): void
    {
        if ($nullable && $locale === null) {
            return;
        }

        if (!in_array($locale, ['en', 'fr'], true)) {
            throw new InvalidArgumentException('Supported locales are "en" and "fr".');
        }
    }

    /** @param mixed $value */
    private static function assertStringList(mixed $value, string $name): void
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new InvalidArgumentException(sprintf('%s must be a list of strings.', $name));
        }

        foreach ($value as $item) {
            if (!is_string($item) || $item === '') {
                throw new InvalidArgumentException(sprintf('%s must be a list of non-empty strings.', $name));
            }
        }
    }

    /** @param array<string, mixed> $values */
    private static function nullableString(array $values, string $key, ?string $default): ?string
    {
        $value = $values[$key] ?? $default;
        if ($value !== null && !is_string($value)) {
            throw new InvalidArgumentException(sprintf('%s must be a string or null.', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $values */
    private static function string(array $values, string $key, string $default): string
    {
        $value = $values[$key] ?? $default;
        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf('%s must be a string.', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $values */
    private static function boolean(array $values, string $key, bool $default): bool
    {
        $value = $values[$key] ?? $default;
        if (!is_bool($value)) {
            throw new InvalidArgumentException(sprintf('%s must be a boolean.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $values
     * @param list<string> $default
     * @return list<string>
     */
    private static function stringList(array $values, string $key, array $default): array
    {
        $value = $values[$key] ?? $default;
        self::assertStringList($value, $key);

        /** @var list<string> $value */
        return $value;
    }
}
