<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex;

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
