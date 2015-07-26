<?php

namespace Chansig\DirectoryIndex;

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
            } else if ($fileInfo->isFile()) {
                $infos['size'] = $fileInfo->getSize();
                $infos['extension'] = $fileInfo->getExtension() === '' ? '' : ('.' . strtolower($fileInfo->getExtension()));
                $infos['type'] = $fileInfo->getExtension() === '' ? '_void' : static::getExtensionType('.' . $fileInfo->getExtension());
                $infos['description'] = static::getExtensionDescription('.' . $fileInfo->getExtension());
                $infos['href'] = rtrim($_SERVER['SCRIPT_NAME'], '/') . '/' . $fileInfo->getFilename();
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
