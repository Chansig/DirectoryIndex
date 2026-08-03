<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex;

final class Sorter
{
    /** @var list<string> */
    public const KEYS = [
        'ascname',
        'descname',
        'asctype',
        'desctype',
        'ascsize',
        'descsize',
        'ascmodified',
        'descmodified',
    ];

    public function normalize(string $key): string
    {
        return in_array($key, self::KEYS, true) ? $key : 'ascname';
    }

    /**
     * @param list<Entry> $entries
     * @return list<Entry>
     */
    public function sort(array $entries, string $key): array
    {
        $key = $this->normalize($key);
        $descending = str_starts_with($key, 'desc');
        $field = substr($key, $descending ? 4 : 3);

        usort($entries, static function (Entry $left, Entry $right) use ($descending, $field): int {
            if ($left->directory !== $right->directory) {
                return $left->directory ? -1 : 1;
            }

            $comparison = match ($field) {
                'type' => strcasecmp($left->type(), $right->type()),
                'size' => ($left->size ?? -1) <=> ($right->size ?? -1),
                'modified' => $left->modifiedAt <=> $right->modifiedAt,
                default => strnatcasecmp($left->name, $right->name),
            };

            if ($comparison === 0) {
                $comparison = strnatcasecmp($left->name, $right->name);
            }

            return $descending ? -$comparison : $comparison;
        });

        return $entries;
    }
}
