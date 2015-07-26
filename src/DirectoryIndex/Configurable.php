<?php

namespace Chansig\DirectoryIndex;

/**
 * Class Configurable
 * @package Chansig\DirectoryIndex
 */
abstract class Configurable implements ConfigurableInterface
{
    static public $settings = array();

    static public function setup($settings)
    {
        static::$settings = array_replace_recursive(static::$settings, $settings);
    }

    static public function getSettings()
    {
        return static::$settings;
    }
}