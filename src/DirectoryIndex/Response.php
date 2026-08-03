<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex;

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
