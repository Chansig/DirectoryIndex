<?php

namespace Chansig\DirectoryIndex;

/**
 * Class Main
 * @package Chansig\DirectoryIndex
 */
class Main
{

    const VERSION = '0.2';

    static protected $settings = array();

    /**
     *
     */
    static public function init()
    {
        static::$settings = array(
            'sorter' => array(),
            'listing' => array(),
            'translation' => array(),
        );
        if (class_exists('Chansig\\DirectoryIndex\\Settings')) {
            static::$settings = array_replace_recursive(static::$settings, Settings::getSettings());
        }

        Listing::setup(static::$settings['listing']);
        Sorter::setup(static::$settings['sorter']);
        Translation::setup(static::$settings['sorter']);
        Theme::setup(static::$settings['sorter']);
    }

    /**
     * @return mixed
     */
    static public function getUri()
    {
        $tmp = parse_url($_SERVER['REQUEST_URI']);
        return $tmp['path'];
    }

    /**
     *
     */
    static public function Exec()
    {

        static::init();
        Listing::parse();
        Theme::display();
    }

}
