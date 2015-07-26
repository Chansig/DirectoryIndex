<?php
/**
 * Directory Index - The simplest PHP directory Index
 *
 * This software distributed under the MIT License
 * http://www.opensource.org/licenses/mit-license.php
 *
 * More info available at https://github.com/Chansig/DirectoryIndex
 *
 * @author Chansig
 * @copyright 2015 Chansig (https://github.com/Chansig)
 */

namespace Chansig\DirectoryIndex;

/**
 * Class Settings
 *
 * Provide default Directory Index settings
 *
 * @package Chansig\DirectoryIndex
 */
class Settings
{
    static public function getSettings()
    {
        return array(
            /**
             * @var array
             */
            'sorter' => array(
                /**
                 * Can be 'ascname', 'descname', 'asctype', 'desctype', 'ascsize', 'descsize', 'ascmodified', 'descmodified'
                 * @see Chansig\DirectoryIndex\Sorter::$sorters
                 *
                 * @var string
                 */
                'default_sorter' => 'ascname',
            ),
            /**
             * @var array
             */
            'listing' => array(
                /**
                 * @var bool
                 */
                'show_dirs' => true,
                /**
                 * @var bool
                 */
                'show_dots' => true,
                /**
                 * @var bool
                 */
                'show_hidden' => false,
                /**
                 * Can be 'text', 'data', 'audio', 'video', '3d', 'image', 'vector', 'page', 'spreadsheet', 'database',
                 * 'executable', 'game', 'cad', 'gis', "web", 'plugin', 'font', 'system', 'settings', 'encoded',
                 * 'compressed', 'disc', 'developer', 'backup', 'misc', 'unknown'
                 */
                'hidden_types' => array(
                    'system',
                    'unknown',
                    'executable',
                    'database',
                ),
            ),
            /**
             * @var array
             */
            'translation' => array(
                /**
                 * @var string
                 */
                'default_locale' => 'en',
            ),
            'theme' => array(),
        );
    }
}
