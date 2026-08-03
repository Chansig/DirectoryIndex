<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex;

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
