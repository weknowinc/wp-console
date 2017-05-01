<?php

namespace WP\Console\Extension;

use WP\Console\Core\Extension\Extension as BaseExtension;

/**
 * Class Extension
 *
 * @package WP\Console\Extension
 */
class Extension extends BaseExtension
{
    /**
     * @param $fullPath
     * @return string
     */
    public function getPath($fullPath = false)
    {
        if ($fullPath) {
            return $this->root . '/' . parent::getPath();
        }
        
        return parent::getPath();
    }
}
