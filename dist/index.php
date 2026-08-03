<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex;

use InvalidArgumentException;
use RuntimeException;
use DirectoryIterator;
use Throwable;

// Configuration: edit these values in the distributed file if needed.
$directoryIndexConfig = [
    'forced_locale' => null,
    'fallback_locale' => 'en',
    'show_hidden' => false,
    'follow_symlinks' => false,
    'blocked_names' => [
        '.env', '.git', '.hg', '.svn', '.ssh', '.htaccess', '.htpasswd',
        'directory-index.config.php',
    ],
    'blocked_extensions' => ['key', 'pem', 'p12', 'pfx'],
];

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

final class DirectoryIndexException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}

final readonly class ResolvedDirectory
{
    public function __construct(
        public string $absolutePath,
        public string $relativePath,
    ) {
    }
}

final class PathResolver
{
    private readonly string $root;

    public function __construct(string $root, private readonly bool $followSymlinks = false)
    {
        $resolvedRoot = realpath($root);
        if ($resolvedRoot === false || !is_dir($resolvedRoot)) {
            throw new DirectoryIndexException('The configured root directory does not exist.', 500);
        }

        $this->root = rtrim($resolvedRoot, DIRECTORY_SEPARATOR);
    }

    public function resolve(string $requestedPath): ResolvedDirectory
    {
        $segments = $this->segments($requestedPath);
        $candidate = $this->root;

        foreach ($segments as $segment) {
            $candidate .= DIRECTORY_SEPARATOR . $segment;
            if (!$this->followSymlinks && is_link($candidate)) {
                throw new DirectoryIndexException('Symbolic links are disabled.', 403);
            }
        }

        $resolved = realpath($candidate);
        if ($resolved === false || !is_dir($resolved)) {
            throw new DirectoryIndexException('Directory not found.', 404);
        }

        if (!$this->contains($resolved)) {
            throw new DirectoryIndexException('The requested directory is outside the published root.', 403);
        }

        return new ResolvedDirectory($resolved, $this->relativePath($resolved));
    }

    public function contains(string $absolutePath): bool
    {
        return $absolutePath === $this->root
            || str_starts_with($absolutePath, $this->root . DIRECTORY_SEPARATOR);
    }

    public function child(string $relativePath, string $name): string
    {
        return ltrim($relativePath . '/' . $name, '/');
    }

    public function parent(string $relativePath): string
    {
        if ($relativePath === '') {
            return '';
        }

        $parent = dirname($relativePath);

        return $parent === '.' ? '' : str_replace('\\', '/', $parent);
    }

    /** @return list<string> */
    private function segments(string $requestedPath): array
    {
        if (str_contains($requestedPath, "\0") || str_contains($requestedPath, '\\')) {
            throw new DirectoryIndexException('Invalid directory path.', 400);
        }

        $requestedPath = trim($requestedPath, '/');
        if ($requestedPath === '') {
            return [];
        }

        $segments = explode('/', $requestedPath);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new DirectoryIndexException('Invalid directory path.', 400);
            }
        }

        return $segments;
    }

    private function relativePath(string $absolutePath): string
    {
        if ($absolutePath === $this->root) {
            return '';
        }

        return str_replace('\\', '/', substr($absolutePath, strlen($this->root) + 1));
    }
}

final readonly class Entry
{
    public function __construct(
        public string $name,
        public string $relativePath,
        public bool $directory,
        public ?int $size,
        public int $modifiedAt,
        public string $extension,
    ) {
    }

    public function type(): string
    {
        if ($this->directory) {
            return 'folder';
        }

        return match ($this->extension) {
            '7z', 'bz', 'bz2', 'gz', 'rar', 'tar', 'tgz', 'zip' => 'archive',
            'avif', 'bmp', 'gif', 'jpeg', 'jpg', 'png', 'svg', 'tif', 'tiff', 'webp' => 'image',
            'aac', 'flac', 'm4a', 'mp3', 'ogg', 'wav' => 'audio',
            'avi', 'm4v', 'mkv', 'mov', 'mp4', 'webm' => 'video',
            'csv', 'doc', 'docx', 'md', 'odt', 'pdf', 'rtf', 'txt', 'xls', 'xlsx' => 'document',
            default => 'file',
        };
    }
}

final readonly class DirectoryBrowser
{
    public function __construct(
        private Config $config,
        private PathResolver $paths,
        private string $scriptFilename,
    ) {
    }

    /** @return list<Entry> */
    public function entries(ResolvedDirectory $directory): array
    {
        try {
            $iterator = new DirectoryIterator($directory->absolutePath);
            $entries = [];

            foreach ($iterator as $item) {
                $entry = $this->entry($item, $directory->relativePath);
                if ($entry !== null) {
                    $entries[] = $entry;
                }
            }

            return $entries;
        } catch (Throwable) {
            throw new DirectoryIndexException('The directory cannot be read.', 403);
        }
    }

    private function entry(DirectoryIterator $item, string $relativeDirectory): ?Entry
    {
        if ($item->isDot() || $this->isExcluded($item->getFilename(), $relativeDirectory)) {
            return null;
        }

        if ($item->isLink() && !$this->config->followSymlinks) {
            return null;
        }

        $realPath = $item->getRealPath();
        if ($realPath === false || !$this->paths->contains($realPath)) {
            return null;
        }

        $isDirectory = $item->isDir();

        return new Entry(
            name: $item->getFilename(),
            relativePath: $this->paths->child($relativeDirectory, $item->getFilename()),
            directory: $isDirectory,
            size: $isDirectory ? null : max(0, (int) $item->getSize()),
            modifiedAt: $item->getMTime(),
            extension: $isDirectory ? '' : strtolower($item->getExtension()),
        );
    }

    private function isExcluded(string $filename, string $relativeDirectory): bool
    {
        if ($relativeDirectory === '' && $filename === $this->scriptFilename) {
            return true;
        }

        $lowercase = strtolower($filename);
        if (!$this->config->showHidden && str_starts_with($filename, '.')) {
            return true;
        }

        foreach ($this->config->blockedNames as $blockedName) {
            $blockedName = strtolower($blockedName);
            if ($lowercase === $blockedName || str_starts_with($lowercase, $blockedName . '.')) {
                return true;
            }
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return $extension !== '' && in_array($extension, $this->config->blockedExtensions, true);
    }
}

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

final class Translator
{
    /** @var array<string, array<string, string>> */
    private const MESSAGES = [
        'en' => [
            'index_of' => 'Index of %s',
            'parent_directory' => 'Parent directory',
            'type' => 'Type',
            'name' => 'Name',
            'size' => 'Size',
            'last_modified' => 'Last modified',
            'empty' => 'This directory is empty.',
            'error' => 'Unable to display this directory',
            'powered_by' => 'Directory Index',
        ],
        'fr' => [
            'index_of' => 'Index de %s',
            'parent_directory' => 'Répertoire parent',
            'type' => 'Type',
            'name' => 'Nom',
            'size' => 'Taille',
            'last_modified' => 'Dernière modification',
            'empty' => 'Ce répertoire est vide.',
            'error' => 'Impossible d’afficher ce répertoire',
            'powered_by' => 'Directory Index',
        ],
    ];

    public function translate(string $locale, string $key): string
    {
        if (!isset(self::MESSAGES[$locale][$key])) {
            throw new InvalidArgumentException(sprintf('Unknown translation "%s" for locale "%s".', $key, $locale));
        }

        return self::MESSAGES[$locale][$key];
    }
}

final readonly class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        public int $status,
        public array $headers,
        public string $body,
    ) {
    }
}

final class SecurityHeaders
{
    /** @return array<string, string> */
    public function all(): array
    {
        return [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data:; "
                . "base-uri 'none'; form-action 'none'; frame-ancestors 'none'",
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ];
    }
}

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

final class Application
{
    private readonly PathResolver $paths;
    private readonly DirectoryBrowser $browser;
    private readonly HtmlRenderer $renderer;
    private readonly Sorter $sorter;
    private readonly LocaleResolver $locales;
    private readonly SecurityHeaders $securityHeaders;

    public function __construct(
        string $root,
        private readonly Config $config,
        string $css,
        string $svgSprite,
        string $scriptFilename = 'index.php',
    ) {
        $this->paths = new PathResolver($root, $config->followSymlinks);
        $this->browser = new DirectoryBrowser($config, $this->paths, $scriptFilename);
        $this->sorter = new Sorter();
        $this->locales = new LocaleResolver();
        $this->securityHeaders = new SecurityHeaders();
        $this->renderer = new HtmlRenderer($css, $svgSprite, new Translator(), $this->paths);
    }

    /**
     * @param array<string, mixed> $server
     * @param array<string, mixed> $query
     */
    public function handle(array $server, array $query): Response
    {
        $acceptLanguage = isset($server['HTTP_ACCEPT_LANGUAGE']) && is_string($server['HTTP_ACCEPT_LANGUAGE'])
            ? $server['HTTP_ACCEPT_LANGUAGE']
            : null;
        $locale = $this->locales->resolve(
            $this->config->forcedLocale,
            $acceptLanguage,
            $this->config->fallbackLocale,
        );
        $scriptName = isset($server['SCRIPT_NAME']) && is_string($server['SCRIPT_NAME'])
            ? $server['SCRIPT_NAME']
            : '/index.php';

        try {
            $requestedPath = $this->queryString($query, 'path', '');
            $sort = $this->sorter->normalize($this->queryString($query, 'sort', 'ascname'));
            $directory = $this->paths->resolve($requestedPath);
            $entries = $this->sorter->sort($this->browser->entries($directory), $sort);

            return new Response(
                200,
                $this->securityHeaders->all(),
                $this->renderer->directory($directory, $entries, $locale, $sort, $scriptName),
            );
        } catch (DirectoryIndexException $exception) {
            return new Response(
                $exception->status,
                $this->securityHeaders->all(),
                $this->renderer->error($locale, $exception->getMessage()),
            );
        }
    }

    /** @param array<string, mixed> $query */
    private function queryString(array $query, string $key, string $default): string
    {
        if (!array_key_exists($key, $query)) {
            return $default;
        }

        if (!is_string($query[$key])) {
            throw new DirectoryIndexException(sprintf('Invalid "%s" query parameter.', $key), 400);
        }

        return $query[$key];
    }
}

const DIRECTORY_INDEX_CSS = <<<'CSS'
:root {
    color-scheme: light dark;
    --background: #f5f7fa;
    --border: #d8dee9;
    --foreground: #1f2937;
    --muted: #64748b;
    --panel: #ffffff;
    --row-hover: #eff6ff;
    --accent: #2563eb;
}

@media (prefers-color-scheme: dark) {
    :root {
        --background: #111827;
        --border: #374151;
        --foreground: #f3f4f6;
        --muted: #9ca3af;
        --panel: #1f2937;
        --row-hover: #273449;
        --accent: #93c5fd;
    }
}

* { box-sizing: border-box; }
body {
    background: var(--background);
    color: var(--foreground);
    font-family: Arial, Helvetica, sans-serif;
    margin: 0;
    min-height: 100vh;
}
main { margin: 0 auto; max-width: 72rem; padding: 2rem 1rem; }
h1 { font-size: clamp(1.5rem, 4vw, 2.25rem); margin: 0 0 1.5rem; overflow-wrap: anywhere; }
.table-wrap { background: var(--panel); border: 1px solid var(--border); border-radius: .75rem; overflow-x: auto; }
table { border-collapse: collapse; min-width: 44rem; width: 100%; }
th, td { border-bottom: 1px solid var(--border); padding: .75rem 1rem; text-align: left; }
th { color: var(--muted); font-size: .8rem; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
th a { align-items: center; color: inherit; display: inline-flex; gap: .35rem; }
tbody tr:last-child td { border-bottom: 0; }
tbody tr:hover { background: var(--row-hover); }
a { color: var(--accent); text-decoration: none; }
a:hover { text-decoration: underline; }
a:focus-visible { outline: 3px solid var(--accent); outline-offset: 3px; }
.entry { align-items: center; color: var(--foreground); display: inline-flex; gap: .6rem; overflow-wrap: anywhere; }
.icon { fill: none; height: 1.15rem; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; vertical-align: middle; width: 1.15rem; }
.mobile-icon { display: none; }
.type-label { clip: rect(0 0 0 0); clip-path: inset(50%); height: 1px; overflow: hidden; position: absolute; white-space: nowrap; width: 1px; }
.size, time { white-space: nowrap; }
.empty { color: var(--muted); padding: 2rem; text-align: center; }
.error { max-width: 42rem; }
.error p { background: var(--panel); border: 1px solid var(--border); border-radius: .75rem; padding: 1rem; }
footer { color: var(--muted); font-size: .75rem; padding: 0 1rem 2rem; text-align: center; }

@media (max-width: 46rem) {
    main { padding: 1.25rem .75rem; }
    .table-wrap { overflow: hidden; }
    table { min-width: 0; }
    th:first-child, td:first-child, th:nth-child(3), td:nth-child(3), th:nth-child(4), td:nth-child(4) { display: none; }
    .mobile-icon { display: inline; flex: 0 0 auto; }
}
CSS;

const DIRECTORY_INDEX_SVG = <<<'SVG'
<svg aria-hidden="true" focusable="false" style="display:none" xmlns="http://www.w3.org/2000/svg">
  <symbol id="icon-folder" viewBox="0 0 24 24"><path d="M3 7.5h7l2-2h9v13H3z"/></symbol>
  <symbol id="icon-parent" viewBox="0 0 24 24"><path d="M4 18V7h16v11M8 11l4-4 4 4"/></symbol>
  <symbol id="icon-file" viewBox="0 0 24 24"><path d="M6 3h8l4 4v14H6zM14 3v5h5"/></symbol>
  <symbol id="icon-document" viewBox="0 0 24 24"><path d="M6 3h8l4 4v14H6zM9 12h6M9 16h6"/></symbol>
  <symbol id="icon-image" viewBox="0 0 24 24"><path d="M4 5h16v14H4zM7 16l4-4 3 3 2-2 2 3M8 9h.01"/></symbol>
  <symbol id="icon-archive" viewBox="0 0 24 24"><path d="M5 4h14v4H5zM6 8h12v12H6zM10 12h4"/></symbol>
  <symbol id="icon-audio" viewBox="0 0 24 24"><path d="M9 18V6l9-2v12M9 18a3 2 0 1 1-3-2 3 2 0 0 1 3 2M18 16a3 2 0 1 1-3-2 3 2 0 0 1 3 2"/></symbol>
  <symbol id="icon-video" viewBox="0 0 24 24"><path d="M4 5h16v14H4zM10 9l5 3-5 3z"/></symbol>
  <symbol id="icon-sort-up" viewBox="0 0 24 24"><path d="M8 14l4-4 4 4"/></symbol>
  <symbol id="icon-sort-down" viewBox="0 0 24 24"><path d="M8 10l4 4 4-4"/></symbol>
</svg>
SVG;

$application = new Application(
    __DIR__,
    Config::fromArray($directoryIndexConfig),
    DIRECTORY_INDEX_CSS,
    DIRECTORY_INDEX_SVG,
    basename(__FILE__),
);
$response = $application->handle($_SERVER, $_GET);
http_response_code($response->status);
foreach ($response->headers as $name => $value) {
    header($name . ': ' . $value);
}
echo $response->body;
