<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex\Tests;

use Chansig\DirectoryIndex\DirectoryIndexException;
use Chansig\DirectoryIndex\Response;
use Chansig\DirectoryIndex\SecurityHeaders;
use PHPUnit\Framework\TestCase;

final class ValueObjectsTest extends TestCase
{
    public function testResponseStoresHttpData(): void
    {
        $response = new Response(201, ['X-Test' => 'yes'], 'body');

        self::assertSame(201, $response->status);
        self::assertSame(['X-Test' => 'yes'], $response->headers);
        self::assertSame('body', $response->body);
    }

    public function testExceptionStoresStatusAndMessage(): void
    {
        $exception = new DirectoryIndexException('Denied', 403);

        self::assertSame('Denied', $exception->getMessage());
        self::assertSame(403, $exception->status);
    }

    public function testSecurityHeadersAreRestrictive(): void
    {
        $headers = (new SecurityHeaders())->all();

        self::assertSame('nosniff', $headers['X-Content-Type-Options']);
        self::assertSame('DENY', $headers['X-Frame-Options']);
        self::assertSame('no-referrer', $headers['Referrer-Policy']);
        self::assertSame(
            "default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data:; "
                . "base-uri 'none'; form-action 'none'; frame-ancestors 'none'",
            $headers['Content-Security-Policy'],
        );
        self::assertStringContainsString('camera=()', $headers['Permissions-Policy']);
    }
}
