<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex;

use RuntimeException;

final class DirectoryIndexException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}
