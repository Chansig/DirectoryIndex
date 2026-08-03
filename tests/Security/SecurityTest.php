<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex\Tests\Security;

use Chansig\DirectoryIndex\Application;
use Chansig\DirectoryIndex\Config;
use Chansig\DirectoryIndex\Tests\FilesystemTestCase;

final class SecurityTest extends FilesystemTestCase
{
    public function testMaliciousFilenameCannotInjectMarkup(): void
    {
        $filename = '"><img src=x onerror=alert(1)>.txt';
        $this->file($filename, 'payload');

        $response = $this->application()->handle(['SCRIPT_NAME' => '/index.php'], []);

        self::assertSame(200, $response->status);
        self::assertStringNotContainsString('<img src=x', $response->body);
        self::assertStringContainsString('&quot;&gt;&lt;img src=x onerror=alert(1)&gt;.txt', $response->body);
        self::assertStringContainsString('%22%3E%3Cimg%20src%3Dx%20onerror%3Dalert%281%29%3E.txt', $response->body);
    }

    public function testTraversalAttemptsCannotEscapeRoot(): void
    {
        $application = $this->application();

        foreach (['..', '../', 'folder/../../', '/etc', "folder\0name", 'folder\\name'] as $path) {
            $response = $application->handle([], ['path' => $path]);
            self::assertContains($response->status, [400, 404]);
            self::assertStringNotContainsString('root:', $response->body);
        }
    }

    public function testSensitiveFilesAreNeverListedByDefault(): void
    {
        $this->file('.env', 'PASSWORD=secret');
        $this->file('.env.production', 'PASSWORD=secret');
        $this->file('private.key', 'secret');
        $this->file('certificate.pem', 'secret');
        $this->file('public.txt', 'safe');

        $body = $this->application()->handle([], [])->body;

        self::assertStringContainsString('public.txt', $body);
        self::assertStringNotContainsString('PASSWORD', $body);
        self::assertStringNotContainsString('private.key', $body);
        self::assertStringNotContainsString('certificate.pem', $body);
    }

    public function testServerValuesAreEscaped(): void
    {
        $this->file('file.txt', 'safe');

        $response = $this->application()->handle(
            [
                'SCRIPT_NAME' => '/index.php" onmouseover="alert(1)',
                'HTTP_ACCEPT_LANGUAGE' => 'en"><script>alert(1)</script>',
            ],
            ['sort' => 'descname'],
        );

        self::assertStringNotContainsString('<script>', $response->body);
        self::assertStringNotContainsString('onmouseover="alert', $response->body);
        self::assertStringContainsString('onmouseover=&quot;alert(1)', $response->body);
    }

    public function testParentLinkNeverAppearsAtPublishedRoot(): void
    {
        $this->file('file.txt', 'safe');

        $response = $this->application()->handle([], []);

        self::assertStringNotContainsString('Parent directory', $response->body);
        self::assertStringNotContainsString('path=..', $response->body);
    }

    private function application(): Application
    {
        return new Application($this->root, new Config(), 'body{}', '<svg></svg>', 'index.php');
    }
}
