<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex\Tests;

use Chansig\DirectoryIndex\Application;
use Chansig\DirectoryIndex\Config;

final class ApplicationTest extends FilesystemTestCase
{
    public function testSuccessfulRequestUsesBrowserLanguageAndSort(): void
    {
        $this->file('index.php', 'script');
        $this->file('b.txt', 'bb');
        $this->file('a.txt', 'a');
        $application = $this->application();

        $response = $application->handle(
            ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR', 'SCRIPT_NAME' => '/downloads/index.php'],
            ['sort' => 'descname'],
        );

        self::assertSame(200, $response->status);
        self::assertSame('text/html; charset=UTF-8', $response->headers['Content-Type']);
        self::assertStringContainsString('Index de /', $response->body);
        self::assertLessThan(strpos($response->body, 'a.txt'), strpos($response->body, 'b.txt'));
    }

    public function testForcedLocaleAndDefaultServerValuesAreUsed(): void
    {
        $this->file('file.txt', 'text');
        $application = $this->application(new Config(forcedLocale: 'fr'));

        $response = $application->handle(['HTTP_ACCEPT_LANGUAGE' => ['invalid']], []);

        self::assertSame(200, $response->status);
        self::assertStringContainsString('Index de /', $response->body);
        self::assertStringContainsString('href="/file.txt"', $response->body);
    }

    public function testChildDirectoryCanBeBrowsed(): void
    {
        $this->file('documents/report.txt', 'report');

        $response = $this->application()->handle(
            ['SCRIPT_NAME' => '/index.php'],
            ['path' => 'documents', 'sort' => 'invalid'],
        );

        self::assertSame(200, $response->status);
        self::assertStringContainsString('Index of /documents', $response->body);
        self::assertStringContainsString('Parent directory', $response->body);
    }

    public function testFilesystemErrorIsRendered(): void
    {
        $response = $this->application()->handle([], ['path' => 'missing']);

        self::assertSame(404, $response->status);
        self::assertStringContainsString('Directory not found.', $response->body);
    }

    public function testInvalidQueryTypesAreRejected(): void
    {
        $pathResponse = $this->application()->handle([], ['path' => []]);
        $sortResponse = $this->application()->handle([], ['sort' => []]);

        self::assertSame(400, $pathResponse->status);
        self::assertStringContainsString('Invalid &quot;path&quot; query parameter.', $pathResponse->body);
        self::assertSame(400, $sortResponse->status);
        self::assertStringContainsString('Invalid &quot;sort&quot; query parameter.', $sortResponse->body);
    }

    private function application(?Config $config = null): Application
    {
        return new Application(
            $this->root,
            $config ?? new Config(),
            'body{}',
            '<svg></svg>',
            'index.php',
        );
    }
}
