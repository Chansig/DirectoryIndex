<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex;

use DirectoryIterator;
use Throwable;

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
