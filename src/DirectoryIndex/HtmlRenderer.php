<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex;

final readonly class HtmlRenderer
{
    public function __construct(
        private string $css,
        private string $svgSprite,
        private Translator $translator,
        private PathResolver $paths,
    ) {
    }

    /** @param list<Entry> $entries */
    public function directory(
        ResolvedDirectory $directory,
        array $entries,
        string $locale,
        string $sort,
        string $scriptName,
    ): string {
        $displayPath = '/' . $directory->relativePath;
        $title = sprintf($this->translator->translate($locale, 'index_of'), $displayPath);
        $rows = '';

        if ($directory->relativePath !== '') {
            $rows .= $this->parentRow($directory, $locale, $sort, $scriptName);
        }

        foreach ($entries as $entry) {
            $rows .= $this->entryRow($entry, $scriptName, $locale, $sort);
        }

        if ($rows === '') {
            $rows = sprintf(
                '<tr><td class="empty" colspan="4">%s</td></tr>',
                $this->escape($this->translator->translate($locale, 'empty')),
            );
        }

        return $this->page(
            locale: $locale,
            title: $title,
            content: sprintf(
                '<main><h1>%s</h1><div class="table-wrap"><table>'
                    . '<thead><tr>%s</tr></thead><tbody>%s</tbody></table></div></main>',
                $this->escape($title),
                $this->headerCells($directory->relativePath, $locale, $sort, $scriptName),
                $rows,
            ),
        );
    }

    public function error(string $locale, string $message): string
    {
        $title = $this->translator->translate($locale, 'error');

        return $this->page(
            locale: $locale,
            title: $title,
            content: sprintf(
                '<main class="error"><h1>%s</h1><p>%s</p></main>',
                $this->escape($title),
                $this->escape($message),
            ),
        );
    }

    private function page(string $locale, string $title, string $content): string
    {
        return sprintf(
            '<!doctype html><html lang="%s"><head><meta charset="utf-8">'
                . '<meta name="viewport" content="width=device-width,initial-scale=1">'
                . '<title>%s</title><style>%s</style></head><body>%s%s'
                . '<footer>%s</footer></body></html>',
            $this->escape($locale),
            $this->escape($title),
            $this->css,
            $this->svgSprite,
            $content,
            $this->escape($this->translator->translate($locale, 'powered_by')),
        );
    }

    private function headerCells(string $path, string $locale, string $sort, string $scriptName): string
    {
        $definitions = [
            ['label' => 'type', 'asc' => 'asctype', 'desc' => 'desctype'],
            ['label' => 'name', 'asc' => 'ascname', 'desc' => 'descname'],
            ['label' => 'size', 'asc' => 'ascsize', 'desc' => 'descsize'],
            ['label' => 'last_modified', 'asc' => 'ascmodified', 'desc' => 'descmodified'],
        ];
        $cells = '';

        foreach ($definitions as $definition) {
            $activeAscending = $sort === $definition['asc'];
            $activeDescending = $sort === $definition['desc'];
            $nextSort = $activeAscending ? $definition['desc'] : $definition['asc'];
            $ariaSort = $activeAscending ? 'ascending' : ($activeDescending ? 'descending' : 'none');
            $cells .= sprintf(
                '<th aria-sort="%s"><a href="%s">%s%s</a></th>',
                $ariaSort,
                $this->escape($this->directoryUrl($scriptName, $path, $nextSort)),
                $this->escape($this->translator->translate($locale, $definition['label'])),
                ($activeAscending || $activeDescending)
                    ? $this->icon($activeAscending ? 'sort-up' : 'sort-down', '')
                    : '',
            );
        }

        return $cells;
    }

    private function parentRow(
        ResolvedDirectory $directory,
        string $locale,
        string $sort,
        string $scriptName,
    ): string {
        $url = $this->directoryUrl($scriptName, $this->paths->parent($directory->relativePath), $sort);

        return sprintf(
            '<tr><td>%s</td><td><a class="entry" href="%s">%s<span>%s</span></a></td><td>—</td><td>—</td></tr>',
            $this->icon('parent', ''),
            $this->escape($url),
            $this->icon('parent', 'mobile-icon'),
            $this->escape($this->translator->translate($locale, 'parent_directory')),
        );
    }

    private function entryRow(Entry $entry, string $scriptName, string $locale, string $sort): string
    {
        $type = $entry->type();
        $url = $entry->directory
            ? $this->directoryUrl($scriptName, $entry->relativePath, $sort)
            : $this->fileUrl($scriptName, $entry->relativePath);

        return sprintf(
            '<tr><td><span class="type-label">%s</span>%s</td>'
                . '<td><a class="entry" href="%s">%s<span>%s%s</span></a></td>'
                . '<td class="size">%s</td><td><time datetime="%s">%s</time></td></tr>',
            $this->escape(ucfirst($type)),
            $this->icon($type, ''),
            $this->escape($url),
            $this->icon($type, 'mobile-icon'),
            $this->escape($entry->name),
            $entry->directory ? '/' : '',
            $entry->size === null ? '—' : $this->escape($this->humanSize($entry->size, $locale)),
            gmdate('c', $entry->modifiedAt),
            gmdate('Y-m-d H:i:s', $entry->modifiedAt) . ' UTC',
        );
    }

    private function directoryUrl(string $scriptName, string $path, string $sort): string
    {
        $parameters = [];
        if ($path !== '') {
            $parameters['path'] = $path;
        }
        if ($sort !== 'ascname') {
            $parameters['sort'] = $sort;
        }

        $query = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        return $scriptName . ($query === '' ? '' : '?' . $query);
    }

    private function fileUrl(string $scriptName, string $relativePath): string
    {
        $base = dirname($scriptName);
        $base = $base === '/' || $base === '.' ? '' : $base;
        $segments = array_map('rawurlencode', explode('/', $relativePath));

        return $base . '/' . implode('/', $segments);
    }

    private function humanSize(int $bytes, string $locale): string
    {
        $units = $locale === 'fr'
            ? ['o', 'Kio', 'Mio', 'Gio', 'Tio', 'Pio']
            : ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
        $value = $bytes;
        $unit = 0;
        $maximumUnit = count($units) - 1;

        while ($value >= 1024 && $unit < $maximumUnit) {
            $value /= 1024;
            ++$unit;
        }

        $formatted = $unit === 0
            ? (string) $bytes
            : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        return $formatted . ' ' . $units[$unit];
    }

    private function icon(string $name, string $class): string
    {
        return sprintf(
            '<svg class="icon %s" aria-hidden="true"><use href="#icon-%s"></use></svg>',
            $this->escape($class),
            $this->escape($name),
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
