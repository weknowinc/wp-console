<?php

/**
 * @file
 * Contains WP\Console\Core\Utils\ChainQueue.
 */

namespace WP\Console\Core\Utils;

/**
 * Class FileQueue
 * @package WP\Console\Core\Utils
 */
class FileQueue
{
    /**
     * @var $commands array
     */
    private $files;

    /**
     * @param $file string
     */
    public function addFile($file)
    {
        $this->files[] = $file;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }
}
