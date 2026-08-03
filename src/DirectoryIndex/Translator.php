<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex;

use InvalidArgumentException;

final class Translator
{
    /** @var array<string, array<string, string>> */
    private const MESSAGES = [
        'en' => [
            'index_of' => 'Index of %s',
            'parent_directory' => 'Parent directory',
            'type' => 'Type',
            'name' => 'Name',
            'size' => 'Size',
            'last_modified' => 'Last modified',
            'empty' => 'This directory is empty.',
            'error' => 'Unable to display this directory',
            'powered_by' => 'Directory Index',
        ],
        'fr' => [
            'index_of' => 'Index de %s',
            'parent_directory' => 'Répertoire parent',
            'type' => 'Type',
            'name' => 'Nom',
            'size' => 'Taille',
            'last_modified' => 'Dernière modification',
            'empty' => 'Ce répertoire est vide.',
            'error' => 'Impossible d’afficher ce répertoire',
            'powered_by' => 'Directory Index',
        ],
    ];

    public function translate(string $locale, string $key): string
    {
        if (!isset(self::MESSAGES[$locale][$key])) {
            throw new InvalidArgumentException(sprintf('Unknown translation "%s" for locale "%s".', $key, $locale));
        }

        return self::MESSAGES[$locale][$key];
    }
}
