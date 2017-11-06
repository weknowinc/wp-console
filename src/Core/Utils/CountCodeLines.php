<?php

/**
 * @file
 * Contains WP\Console\Core\Command\CountCodeLines.
 */

namespace WP\Console\Core\Utils;

/**
 * Class CountCodeLines
 *
 * @package WP\Console\Core\Utils
 */
class CountCodeLines
{
    /**
     * @var $countCodeLine integer
     */
    private $countCodeLine;

    /**
     * @param $countCodeLine integer
     */
    public function addCountCodeLines($countCodeLine)
    {
        $this->countCodeLine = $this->countCodeLine + $countCodeLine;
    }

    /**
     * @return integer
     */
    public function getCountCodeLines()
    {
        return $this->countCodeLine;
    }
}
