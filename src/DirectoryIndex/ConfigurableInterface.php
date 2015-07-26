<?php

namespace Chansig\DirectoryIndex;

/**
 * Interface ConfigurableInterface
 * @package Chansig\DirectoryIndex
 */
interface ConfigurableInterface
{
    static public function setup($settings);

    static public function getSettings();
}
