<?php

namespace Chansig\DirectoryIndex;

/**
 * Class Translation
 * @package Chansig\DirectoryIndex
 */
class Translation extends Configurable
{
    static public $settings = array(
        'default_locale' => 'en'
    );

    /**
     * @return array
     */
    static function getTranslations()
    {
        return array(
            'en' => array(
                'index_of' => 'Index of %s',
                'type' => 'Type',
                'name' => 'Name',
                'last_modified' => 'Last modified',
                'size' => 'Size',
                'sizes' => array('B', 'K', 'M', 'G', 'T', 'P'),
            ),
            'fr' => array(
                'index_of' => 'Liste des fichiers de %s',
                'type' => 'Type',
                'name' => 'Nom',
                'last_modified' => 'Modifié le',
                'size' => 'Taille',
                'sizes' => array('o', 'Ko', 'Mo', 'Go', 'To', 'Po'),
            )
        );
    }

    /**
     * @return string
     */
    static public function getDefaultLocale()
    {
        return static::$settings['default_locale'];
    }

    /**
     * @return string
     */
    static public function getCanonicalLocale()
    {
        try {
            $locale = \Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            return \Locale::canonicalize($locale);
        } catch (\Exception $e) {
            return static::getDefaultLocale();
        }
    }


    /**
     * @param $key
     * @return mixed
     */
    static public function trans($key)
    {
        $locale = static::getCanonicalLocale();
        $defaultLocale = static::getDefaultLocale();
        $translation = static::getTranslations();
        if (isset($translation[$locale]) && isset($translation[$locale][$key])) {
            return $translation[$locale][$key];
        } else if (isset($translation[$defaultLocale]) && isset($translation[$defaultLocale][$key])) {
            return $translation[$defaultLocale][$key];
        }
        return '';
    }
}
