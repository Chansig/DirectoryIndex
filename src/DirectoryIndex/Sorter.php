<?php

namespace Chansig\DirectoryIndex;

/**
 * Class Sorter
 * @package Chansig\DirectoryIndex
 */
class Sorter extends Configurable
{
    static public $settings = array(
        'param_name' => 'sort',
        'default_sorter' => 'ascname',
    );
    /**
     * @var array
     */
    static protected $sorters = array('ascname', 'descname', 'asctype', 'desctype', 'ascsize', 'descsize', 'ascmodified', 'descmodified');

    /**
     * @param $files
     */
    static public function sort(&$files)
    {
        uasort($files, array('Static', static::getSorterMethod()));
    }

    /**
     * @return string
     */
    static public function getSorterMethod()
    {
        $methods = array(
            'asctype' => 'sortAscType',
            'desctype' => 'sortDescType',
            'ascsize' => 'sortAscSize',
            'descsize' => 'sortDescSize',
            'ascmodified' => 'sortAscModified',
            'descmodified' => 'sortDescModified',
            'ascname' => 'sortNameAsc',
            'descname' => 'sortNameDesc',
        );

        return $methods[static::getSortKey()];
    }

    /**
     * @return string
     */
    static public function getSortKey()
    {
        if (isset($_GET) && isset($_GET[static::$settings['param_name']]) && in_array($_GET[static::$settings['param_name']], static::$sorters)) {
            return $_GET[static::$settings['param_name']];
        } else {
            return static::$settings['default_sorter'];
        }
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    static public function sortNameDesc($a, $b)
    {
        return static::sortNameAsc($b, $a);
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    static public function sortNameAsc($a, $b)
    {
        if ($a['filename'] === $b['filename']) {
            return 0;
        }

        return ($a['filename'] < $b['filename']) ? -1 : 1;
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    static public function sortAscType($a, $b)
    {
        if ($a['type'] == $b['type']) {
            return 0;
        }

        return ($a['type'] < $b['type']) ? -1 : 1;
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    static public function sortDescType($a, $b)
    {
        return static::sortAscType($b, $a);
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    static public function sortAscSize($a, $b)
    {
        if ($a['size'] == $b['size']) {
            return 0;
        }

        return ($a['size'] < $b['size']) ? -1 : 1;
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    static public function sortDescSize($a, $b)
    {
        return static::sortAscSize($b, $a);
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    static public function sortAscModified($a, $b)
    {
        if ($a['mtime'] == $b['mtime']) {
            return 0;
        }
        return ($a['mtime'] < $b['mtime']) ? -1 : 1;
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    static public function sortDescModified($a, $b)
    {
        return static::sortAscModified($b, $a);
    }
}
