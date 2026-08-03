<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex;

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
