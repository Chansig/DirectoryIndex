<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex;

final class LocaleResolver
{
    /** @var list<string> */
    private const SUPPORTED = ['en', 'fr'];

    public function resolve(?string $forcedLocale, ?string $acceptLanguage, string $fallbackLocale): string
    {
        if ($forcedLocale !== null) {
            return $forcedLocale;
        }

        if ($acceptLanguage === null || $acceptLanguage === '') {
            return $fallbackLocale;
        }

        $candidates = [];
        foreach (explode(',', $acceptLanguage) as $position => $part) {
            $candidate = $this->candidate($part, $position);
            if ($candidate !== null) {
                $candidates[] = $candidate;
            }
        }

        usort(
            $candidates,
            static fn (array $left, array $right): int =>
                ($right['quality'] <=> $left['quality']) ?: ($left['position'] <=> $right['position']),
        );

        return $candidates[0]['locale'] ?? $fallbackLocale;
    }

    /** @return array{locale: string, quality: float, position: int}|null */
    private function candidate(string $part, int $position): ?array
    {
        $pieces = array_map('trim', explode(';', $part));
        $languageRange = strtolower($pieces[0]);
        $quality = 1.0;

        foreach ($pieces as $parameter) {
            if (preg_match('/^q=(0(?:\.\d{1,3})?|1(?:\.0{1,3})?)$/', $parameter, $matches) === 1) {
                $quality = (float) $matches[1];
            }
        }

        $primary = explode('-', $languageRange)[0];
        if ($quality <= 0 || !in_array($primary, self::SUPPORTED, true)) {
            return null;
        }

        return ['locale' => $primary, 'quality' => $quality, 'position' => $position];
    }
}
