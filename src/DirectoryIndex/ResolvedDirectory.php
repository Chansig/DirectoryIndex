<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex;

final readonly class ResolvedDirectory
{
    public function __construct(
        public string $absolutePath,
        public string $relativePath,
    ) {
    }
}
