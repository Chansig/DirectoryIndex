Directory Index - The simplest PHP directory Index
==================================================
Created by, [Chansig] (https://github.com/Chansig)


Introduction
------------

Directory Index is a simple PHP script that lists the contents of any web-accessable directory and
allows navigating there within. Simply upload **index.php** file to any directory and get immediate
access to all files and sub-direcories under that directory. Directory Index is written in PHP 5.3+ and
distributed under the [MIT License](http://www.opensource.org/licenses/mit-license.php).


Features
--------

  * Extremely simple installation
  * Creates on-the-fly listing of any web-accessable directory
  * Custimizable sort order of files/folders
  * Easily define hidden files or file type to be excluded from the listing


Requirements
------------

Directory Index requires PHP 5.3+ to work properly.  For more information on PHP, please visit <http://www.php.net>.


Installation
------------

  * Upload `index.php` to the folder you want listed. That's all!
  
  * For more settings:
     * copy index.php or execute generate.php with the destination in argument and rename index.php.dist in index.php.
        
        ex:        
        php genrate.php /var/www/mysite
     
     * Edit the new index.php and change values in Setting class
     * Upload `index.php` to the folder you want listed.

Troubleshooting
---------------

Ensure you have the latest version of Directory Index installed.

Verify that you have PHP 5.3 or later installed. You can verify your PHP version by running:

    php --version


Contact Info
------------

Find a problem or bug with Directory Index?
[Open an issue](https://github.com/Chansig/DirectoryIndex/issues) on GitHub.


License
-------

Directory Index is distributed under the terms of the
[MIT License](http://www.opensource.org/licenses/mit-license.php).


Copyright 2015 [Chansig] (https://github.com/Chansig)
