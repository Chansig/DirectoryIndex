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
/**
 * Interface ConfigurableInterface
 * @package Chansig\DirectoryIndex
 */
interface ConfigurableInterface
{
    static public function setup($settings);
    static public function getSettings();
}
/**
 * Class Listing
 * @package Chansig\DirectoryIndex
 */
class Listing extends Configurable
{
    /**
     * @var array
     */
    static public $settings = array(
        'show_dots' => false,
        'show_dirs' => false,
        'show_hidden' => false,
        'hidden_types' => array(
            'system',
            'unknown',
            'executable',
            'database',
        ),
    );
    /**
     * @var array
     */
    static public $files = array();
    /**
     * Directory Index
     */
    static public function parse()
    {
//        if (!static::$settings['show_dots']) {
//            ini_set('open_basedir', __DIR__);
//        }
        $directory = new \DirectoryIterator('.');
        foreach ($directory as $fileInfo) {
            if ($fileInfo->isDot() && !static::$settings['show_dots']) {
                continue;
            }
            if ($fileInfo->isDot() && static::$settings['show_dots'] && $_SERVER['SCRIPT_NAME'] === '/') {
                continue;
            }
            if ($fileInfo->isDot() && static::$settings['show_dots'] && $fileInfo->getFilename() === '.') {
                continue;
            }
            if ($fileInfo->isDir() && !static::$settings['show_dirs']) {
                continue;
            }
            if (!$fileInfo->isDir() && strpos($fileInfo->getFilename(), '.') === 0 && !static::$settings['show_hidden']) {
                continue;
            }
            if ($fileInfo->getRealPath() === realpath(__FILE__)) {
                continue;
            }
            if (in_array(static::getExtensionType('.' . $fileInfo->getExtension()), static::$settings['hidden_types'])) {
                continue;
            }
            $infos = array(
                'filename' => $fileInfo->getFilename(),
                'size' => $fileInfo->getSize(),
                'mtime' => $fileInfo->getMTime(),
            );
            if ($fileInfo->isDir() && static::$settings['show_dirs'] && (!$fileInfo->isDot() || static::$settings['show_dots'])) {
                $infos['size'] = -1;
                $infos['extension'] = null;
                $infos['type'] = '_folder';
                $infos['description'] = '';
                $infos['href'] = rtrim($_SERVER['SCRIPT_NAME'], '/') . '/' . $fileInfo->getFilename() . '/';
                $infos['href'] = rtrim($_SERVER['SCRIPT_NAME'], '/') . '/' . $fileInfo->getFilename();
            } else if ($fileInfo->isFile()) {
                $infos['size'] = $fileInfo->getSize();
                $infos['extension'] = $fileInfo->getExtension() === '' ? '' : ('.' . strtolower($fileInfo->getExtension()));
                $infos['type'] = $fileInfo->getExtension() === '' ? '_void' : static::getExtensionType('.' . $fileInfo->getExtension());
                $infos['description'] = static::getExtensionDescription('.' . $fileInfo->getExtension());
                $infos['href'] = rtrim(str_replace(basename(__FILE__), '', $_SERVER['SCRIPT_NAME']), '/') . '/' . $fileInfo->getFilename();
            }
            static::$files[] = $infos;
        }
    }
    /**
     * @param $extension
     * @return int|string
     */
    static protected function getExtensionDescription($extension)
    {
        $types = static::getExtensionTypes();
        foreach ($types as $type => $arrayExt) {
            if (array_key_exists($extension, $arrayExt)) {
                return $arrayExt[$extension];
            }
        }
        return '';
    }
    /**
     * @param $extension
     * @return int|string
     */
    static protected function getExtensionType($extension)
    {
        if ($extension === '.') {
            return '_folder';
        }
        $types = static::getExtensionTypes();
        foreach ($types as $type => $arrayExt) {
            if (array_key_exists($extension, $arrayExt)) {
                return $type;
            }
        }
        return 'unknown';
    }
    /**
     * From http://fileinfo.com/filetypes/common
     *
     * @return array
     */
    static protected function getExtensionTypes()
    {
        return array(
            'text' => array(
                '.doc' => 'Microsoft Word Document',
                '.docx' => 'Microsoft Word Open XML Document',
                '.log' => 'Log File',
                '.msg' => 'Outlook Mail Message',
                '.odt' => 'OpenDocument Text Document',
                '.pages' => 'Pages Document',
                '.rtf' => 'Rich Text Format File',
                '.tex' => 'LaTeX Source Document',
                '.txt' => 'Plain Text File',
                '.wpd' => 'WordPerfect Document',
                '.wps' => 'Microsoft Works Word Processor Document'
            ),
            'data' => array(
                '.csv' => 'Comma Separated Values File',
                '.dat' => 'Data File',
                '.gbr' => 'Gerber File',
                '.ged' => 'GEDCOM Genealogy Data File',
                '.key' => 'Keynote Presentation',
                '.keychain' => 'Mac OS X Keychain File',
                '.pps' => 'PowerPoint Slide Show',
                '.ppt' => 'PowerPoint Presentation',
                '.pptx' => 'PowerPoint Open XML Presentation',
                '.sdf' => 'Standard Data File',
                '.tar' => 'Consolidated Unix File Archive',
                '.tax2012' => 'TurboTax 2012 Tax Return',
                '.tax2014' => 'TurboTax 2014 Tax Return',
                '.vcf' => 'vCard File',
                '.xml' => 'XML File',
            ),
            'audio' => array(
                '.aif' => 'Audio Interchange File Format',
                '.iff' => 'Interchange File Format',
                '.m3u' => 'Media Playlist File',
                '.m4a' => 'MPEG-4 Audio File',
                '.mid' => 'MIDI File',
                '.mp3' => 'MP3 Audio File',
                '.mpa' => 'MPEG-2 Audio File',
                '.ra' => 'Real Audio File',
                '.wav' => 'WAVE Audio File',
                '.wma' => 'Windows Media Audio File',
                '.ogg' => 'Ogg Vorbis Audio File',
                '.flac' => 'Free Lossless Audio Codec File',
            ),
            'video' => array(
                '.3g2' => '3GPP2 Multimedia File',
                '.3gp' => '3GPP Multimedia File',
                '.asf' => 'Advanced Systems Format File',
                '.asx' => 'Microsoft ASF Redirector File',
                '.avi' => 'Audio Video Interleave File',
                '.flv' => 'Flash Video File',
                '.m4v' => 'iTunes Video File',
                '.mov' => 'Apple QuickTime Movie',
                '.mp4' => 'MPEG-4 Video File',
                '.mpg' => 'MPEG Video File',
                '.rm' => 'Real Media File',
                '.srt' => 'SubRip Subtitle File',
                '.swf' => 'Shockwave Flash Movie',
                '.vob' => 'DVD Video Object File',
                '.wmv' => 'Windows Media Video File',
                '.3gp2' => "3GPP Multimedia File",
                '.3gpp' => '3GPP Media File',
                '.mkv' => 'Matroska Video File',
                '.ogv' => 'Ogg Video File',
                '.webm' => 'WebM Video File',
            ),
            '3d' => array(
                '.3dm' => 'Rhino 3D Model',
                '.3ds' => '3D Studio Scene',
                '.max' => '3ds Max Scene File',
                '.obj' => 'Wavefront 3D Object File',
            ),
            'image' => array(
                '.bmp' => 'Bitmap Image File',
                '.dds' => 'DirectDraw Surface',
                '.gif' => 'Graphical Interchange Format File',
                '.jpg' => 'JPEG Image',
                '.jpeg' => 'JPEG Image',
                '.png' => 'Portable Network Graphic',
                '.psd' => 'Adobe Photoshop Document',
                '.pspimage' => 'PaintShop Pro Image',
                '.tga' => 'Targa Graphic',
                '.thm' => 'Thumbnail Image File',
                '.tif' => 'Tagged Image File',
                '.tiff' => 'Tagged Image File Format',
                '.yuv' => 'YUV Encoded Image File',
            ),
            'vector' => array(
                '.ai' => 'Adobe Illustrator File',
                '.eps' => 'Encapsulated PostScript File',
                '.ps' => 'PostScript File',
                '.svg' => 'Scalable Vector Graphics File',
                '.drw' => 'Drawing File',
            ),
            'page' => array(
                '.indd' => 'Adobe InDesign Document',
                '.pct' => 'Picture File',
                '.pdf' => 'Portable Document Format File',
            ),
            'spreadsheet' => array(
                '.xlr' => 'Works Spreadsheet',
                '.xls' => 'Excel Spreadsheet',
                '.xlsx' => 'Microsoft Excel Open XML Spreadsheet',
            ),
            'database' => array(
                '.accdb' => 'Access 2007 Database File',
                '.db' => 'Database File',
                '.dbf' => 'Database File',
                '.mdb' => 'Microsoft Access Database',
                '.pdb' => 'Program Database',
                '.sql' => 'Structured Query Language Data File',
            ),
            "executable" => array(
                '.apk' => 'Android Package File',
                '.app' => 'Mac OS X Application',
                '.bat' => 'DOS Batch File',
                '.cgi' => 'Common Gateway Interface Script',
                '.com' => 'DOS Command File',
                '.exe' => 'Windows Executable File',
                '.gadget' => 'Windows Gadget',
                '.jar' => 'Java Archive File',
                '.pif' => 'Program Information File',
                '.vb' => 'VBScript File',
                '.wsf' => 'Windows Script File',
                '.phar' => 'JavaScript Object Notation File',
                '.cmd' => 'Windows Command File',
            ),
            'game' => array(
                '.dem' => 'Video Game Demo File',
                '.gam' => 'Saved Game File',
                '.nes' => 'Nintendo (NES) ROM File',
                '.rom' => 'N64 Game ROM File',
                '.sav' => 'Saved Game',
            ),
            'cad' => array(
                '.dwg' => 'AutoCAD Drawing Database File',
                '.dxf' => 'Drawing Exchange Format File',
            ),
            'gis' => array(
                '.gpx' => 'GPS Exchange File',
                '.kml' => 'Keyhole Markup Language File',
                '.kmz' => 'Google Earth Placemark File',
            ),
            'web' => array(
                '.asp' => 'Active Server Page',
                '.aspx' => 'Active Server Page Extended File',
                '.cer' => 'Internet Security Certificate',
                '.cfm' => 'ColdFusion Markup File',
                '.csr' => 'Certificate Signing Request File',
                '.css' => 'Cascading Style Sheet',
                '.htm' => 'Hypertext Markup Language File',
                '.html' => 'Hypertext Markup Language File',
                '.js' => 'JavaScript File',
                '.jsp' => 'Java Server Page',
                '.php' => 'PHP Source Code File',
                '.rss' => 'Rich Site Summary',
                '.xhtml' => 'Extensible Hypertext Markup Language File',
                '.json' => 'JavaScript Object Notation File',
            ),
            'plugin' => array(
                '.crx' => 'Chrome Extension',
                '.plugin' => 'Mac OS X Plug-in',
            ),
            'font' => array(
                '.fnt' => 'Windows Font File',
                '.fon' => 'Generic Font File',
                '.otf' => 'OpenType Font',
                '.ttf' => 'TrueType Font',
                '.eot' => 'Embedded OpenType Font',
                '.woff' => 'Web Open Font Format File',
            ),
            'system' => array(
                '.cab' => 'Windows Cabinet File',
                '.cpl' => 'Windows Control Panel Item',
                '.cur' => 'Windows Cursor',
                '.deskthemepack' => 'Windows 8 Desktop Theme Pack File',
                '.dll' => 'Dynamic Link Library',
                '.dmp' => 'Windows Memory Dump',
                '.drv' => 'Device Driver',
                '.icns' => 'Mac OS X Icon Resource File',
                '.ico' => 'Icon File',
                '.lnk' => 'Windows File Shortcut',
                '.sys' => 'Windows System File',
            ),
            'settings' => array(
                '.cfg' => 'Configuration File',
                '.ini' => 'Windows Initialization File',
                '.prf' => 'Outlook Profile File',
            ),
            'encoded' => array(
                '.hqx' => 'BinHex 4.0 Encoded File',
                '.mim' => 'Multi-Purpose Internet Mail Message File',
                '.uue' => 'Uuencoded File',
            ),
            'compressed' => array(
                '.7z' => '7-Zip Compressed File',
                '.cbr' => 'Comic Book RAR Archive',
                '.deb' => 'Debian Software Package',
                '.gz' => 'Gnu Zipped Archive',
                '.pkg' => 'Mac OS X Installer Package',
                '.rar' => 'WinRAR Compressed Archive',
                '.rpm' => 'Red Hat Package Manager File',
                '.sitx' => 'StuffIt X Archive',
                '.tar.gz' => 'Compressed Tarball File',
                '.zip' => 'Zipped File',
                '.zipx' => 'Extended Zip File',
                '.bz' => '	Bzip Compressed File',
                '.box' => 'Microsoft Store Download File'
            ),
            'disc' => array(
                '.bin' => 'Binary Disc Image',
                '.cue' => 'Cue Sheet File',
                '.dmg' => 'Mac OS X Disk Image',
                '.iso' => 'Disc Image File',
                '.mdf' => 'Media Disc Image File',
                '.toast' => 'Toast Disc Image',
                '.vcd' => 'Virtual CD',
            ),
            'developer' => array(
                '.c' => 'C/C++ Source Code File',
                '.class' => 'Java Class File',
                '.cpp' => 'C++ Source Code File',
                '.cs' => 'Visual C# Source Code File',
                '.dtd' => 'Document Type Definition File',
                '.fla' => 'Adobe Flash Animation',
                '.h' => 'C/C++/Objective-C Header File',
                '.java' => 'Java Source Code File',
                '.lua' => 'Lua Source File',
                '.m' => 'Objective-C Implementation File',
                '.pl' => 'Perl Script',
                '.py' => 'Python Sc ript',
                '.sh' => 'Bash Shell Script',
                '.sln' => 'Visual Studio Solution File',
                '.swift' => 'Swift Source Code File',
                '.vcxproj' => 'Visual C++ Project',
                '.xcodeproj' => 'Xcode Project',
                '.erb' => 'Ruby ERB Script',
                '.rb' => 'Ruby Source Code',
                '.md' => 'Markdown Documentation File',
            ),
            'backup' => array(
                '.bak' => 'Backup File',
                '.tmp' => 'Temporary File',
            ),
            'misc' => array(
                '.crdownload' => 'Chrome Partially Downloaded File',
                '.ics' => 'Calendar File',
                '.msi' => 'Windows Installer Package',
                '.part' => 'Partially Downloaded File',
                '.torrent' => 'BitTorrent File',
            ),
        );
    }
}
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
     * @return mixed
     */
    static public function getDir()
    {
        $tmp = static::getUri();
        return str_replace(basename(__FILE__), '',$tmp);
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
                'show_dirs' => false,
                /**
                 * @var bool
                 */
                'show_dots' => false,
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
            'image' => 'fa-photo',
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
        <title>' . sprintf(Translation::trans('index_of'), Main::getDir()) . '</title>';
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
        return sprintf('<h1><a href="%s">%s</a></h1>', Main::getDir(), sprintf(Translation::trans('index_of'), Main::getDir()));
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
        $uri = Main::getDir();
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
Main::Exec();
