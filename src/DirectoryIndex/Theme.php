<?php

namespace Chansig\DirectoryIndex;

/**
 * Class Theme
 * @package Chansig\DirectoryIndex
 */
class Theme extends Configurable
{
    /**
     * @var array
     */
    static public $settings = array();

    /**
     * @return string
     */
    static protected function getIconDefaultType()
    {
        return 'fa-question';
    }

    /**
     * @param $type
     * @param string $ext
     * @return string
     */
    static public function getIconType($type, $ext = '')
    {
        $types = static::getIconTypes();
        if (isset($types[$type . $ext])) {
            return $types[$type . $ext];
        } elseif (isset($types[$type])) {
            return $types[$type];
        } else {
            return static::getIconDefaultType();
        }
    }

    /**
     * @return array
     */
    static public function getIconTypes()
    {
        return array(
            '_folder' => 'fa-folder',
            '_void' => 'fa-file-o',
            'text' => 'fa-file-text-o',
            'text.doc' => 'fa-file-word-o',
            'text.docx' => 'fa-file-word-o',
            'data' => 'fa-table',
            'data.ppt' => 'fa-file-powerpoint-o',
            'audio' => 'fa-music',
            'video' => 'fa-youtube-play',
            '3d' => 'fa-cube ',
            'image' => 'fa-photo-o',
            'vector' => 'fa-picture-o',
            'page' => 'fa-file-o',
            'page.pdf' => 'fa-file-pdf-o ',
            'spreadsheet' => 'fa-file-excel-o',
            'database' => 'fa-database',
            'executable' => 'fa-terminal',
            'game' => 'fa-gamepad',
            'cad' => 'fa-file-image-o',
            'gis' => 'fa-globe ',
            "web" => 'fa-code',
            'plugin' => 'fa-puzzle-piece',
            'font' => 'fa-font',
            'system' => 'fa-cogs',
            'settings' => 'fa-wrench',
            'encoded' => 'fa-briefcase',
            'compressed' => 'fa-file-archive-o',
            'disc' => 'fa-hdd-o',
            'developer' => 'fa-code',
            'backup' => 'fa-floppy-o',
            'misc' => 'fa-file',
            'misc.ics' => 'fa-calendar',
            'unknown' => 'fa-question',
        );
    }

    /**
     * Display directory listing
     */
    static public function display()
    {
        Sorter::sort(Listing::$files);

        $content = '<!DOCTYPE html>';
        $content .= sprintf('
<html xmlns="http://www.w3.org/1999/xhtml" lang="%s">', Translation::getCanonicalLocale());
        $content .= '
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . sprintf(Translation::trans('index_of'), Main::getUri()) . '</title>';
        $content .= static::getStyle();

        $content .= '
    </head>
    <body>';
        $content .= static::getTitle();
        $content .= '
        <table align="center">';
        $content .= static::getHeader();
        foreach (Listing::$files as $file) {
            $content .= static::getRow($file);
        }
        $content .= static::getFooter();
        $content .= '
        </table>';
        $content .= '
    </body>
</html>';
        die($content);
    }

    /**
     * @return string
     */
    static function getStyle()
    {
        return '
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
        <style type="text/css">
            *{font-family: Arial, sans-serif;font-size: 18px}
            h1{text-align: center}
            .footer{text-align: center;font-size: 12px}
            .footer a{font-size: 12px}
            a{color:#000000; text-decoration: none}
            a:hover{text-decoration: underline}
            a:visited{color:#555555}
            a:active{color:#34302d}
            a i.fa{
                font-size: 16px
            }
            h1 a, h1 a:visited{color:#000000;}
            table {
                border-spacing: 10px;
            }
            th {
                text-align: left;
            }
            th.date, th.size, th.type {
                text-align: right;
                text-wrap: none;
            }
            th a, th a:visited{
                text-align: left;
                color:#000000;
                text-decoration: none;
                            font-size: 18px

            }
            td.date{font-size: 14px; text-align: right}
            td.size{font-size: 16px; text-align: right}
            td.name{
                padding-right: 30px;
                width:50%;
            }
            td.type{
               ; text-align: right
            }
        </style>';
    }

    /**
     * @return string
     */
    static function getTitle()
    {
        return sprintf('<h1><a href="%s">%s</a></h1>', Main::getUri(), sprintf(Translation::trans('index_of'), Main::getUri()));
    }

    /**
     * @return string
     */
    static protected function getHeader()
    {
        $key = Sorter::getSortKey();

        if ($key === 'asctype') {
            $iconSortType = static::getMenuLink('desctype', 'type', 'asc');
        } elseif ($key === 'desctype') {
            $iconSortType = static::getMenuLink('asctype', 'type', 'desc');
        } else {
            $iconSortType = static::getMenuLink('asctype', 'type');
        }

        if ($key === 'ascname') {
            $iconSortName = static::getMenuLink('descname', 'name', 'asc');

        } elseif ($key === 'descname') {
            $iconSortName = static::getMenuLink('ascname', 'name', 'desc');
        } else {
            $iconSortName = static::getMenuLink('ascname', 'name');
        }

        if ($key === 'ascsize') {
            $iconSortSize = static::getMenuLink('descsize', 'size', 'asc');
        } elseif ($key === 'descsize') {
            $iconSortSize = static::getMenuLink('ascsize', 'size', 'desc');
        } else {
            $iconSortSize = static::getMenuLink('descsize', 'size');
        }

        if ($key === 'ascmodified') {
            $iconSortModified = static::getMenuLink('descmodified', 'last_modified', 'asc');
        } elseif ($key === 'descmodified') {
            $iconSortModified = static::getMenuLink('ascmodified', 'last_modified', 'desc');
        } else {
            $iconSortModified = static::getMenuLink('ascmodified', 'last_modified');
        }

        $header = sprintf('<tr><th class="type">%s</th><th>%s</th><th class="size">%s</th><th class="date">%s</th></tr>' . "\n", $iconSortType, $iconSortName, $iconSortSize, $iconSortModified);
        $header .= '<tr><td colspan="4"  style="border-bottom: 1px solid black;"></td></tr>' . "\n";

        return $header;
    }

    /**
     * @return string
     */
    static protected function getFooter()
    {
        $footer = '<tr><td colspan="4"  style="border-bottom: 1px solid black;"></td></tr>' . "\n";
        $footer .= sprintf('<tr><td colspan="4" class="footer">Powered by <a href="https://github.com/Chansig/DirectoryIndex">Chansig/DirectoryIndex v%s</a></td></tr>' . "\n", Main::VERSION);

        return $footer;
    }

    static protected function getMenuLink($sort, $type, $icon = '')
    {
        $uri = Main::getUri();
        if ('' === $icon) {
            $icon = 'fa fa-sort';
        } else {
            $icon = sprintf('fa fa-sort-%s', $icon);
        }

        return sprintf('<a href="%s?sort=%s"><i class="%s"></i>&nbsp;%s</a>', $uri, $sort, $icon, Translation::trans($type));
    }

    /**
     * @param $file
     * @return string
     */
    static protected function getRow($file)
    {
        $isDir = false;
        if ($file['type'] === null) {
            $isDir = true;
        }
        $date = new \DateTime();
        $date->setTimestamp($file['mtime']);

        $content = '<tr>';
        if (!is_null($file['extension'])) {
            $content .= '<td class="type">' . sprintf('<i class="fa %s" title="%s"></i>', static::getIconType($file['type'], $file['extension']), $file['description']) . '</td>';
        } else {
            $content .= '<td class="type">' . sprintf('<i class="fa %s"></i>', static::getIconType($file['type'], $file['extension'])) . '</td>';
        }
        $content .= '<td class="name">' . sprintf('<a href="%s">%s</a>', $file['href'], $file['filename']) . '</td>';

        if (!$isDir) {
            $content .= '<td class="size">' . sprintf('%s', static::getHumanFilesize($file['size'])) . '</td>';
        } else {
            $content .= '<td class="size">' . sprintf('<div>-</div>') . '</td>';
        }

        $content .= '<td class="date">' . sprintf('%s', $date->format('Y-m-d H:i:s')) . '</td>';

        $content .= '</tr>' . "\n";

        return $content;
    }

    /**
     * @param $bytes
     * @param int $decimals
     * @return string
     */
    static protected function getHumanFilesize($bytes, $decimals = 2)
    {
        if (-1 === $bytes) {
            return '-';
        }
        $sizes = Translation::trans('sizes');
        $factor = floor((strlen($bytes) - 1) / 3);
        $size = sprintf("%.{$decimals}f", $bytes / pow(1024, $factor));
        if ($size !== sprintf("%.{$decimals}f", 0, $factor)) {
            $size = rtrim($size, '.0');
        } else {
            $size = 0;
        }
        return $size . $sizes[(int)$factor];
    }
}
