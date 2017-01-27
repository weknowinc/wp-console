<?php

/**
 * @file
 * Contains WP\Console\Core\Generator\SiteInstallGenerator.
 */
namespace WP\Console\Core\Generator;

/**
 * Class InitGenerator
 * @package Drupal\Console\Core\Generator
 */
class SiteInstallGenerator extends Generator
{
    /**
     * @param string  $userHome
     * @param string  $executableName
     * @param boolean $override
     * @param string  $destination
     * @param array   $configParameters
     */
    public function generate(
        $root,
        $override,
        $configParameters
    ) {
        $configParameters = array_map(
            function ($item) {
                if (is_bool($item)) {
                    return $item?"true":"false";
                }
                return $item;
            },
            $configParameters
        );

        $configFile = $root . '/wp-config.php';

        if (file_exists($configFile) && $override) {
            copy(
                $configFile,
                $configFile . '.old'
            );
        }

        # Render wordpress config
        $this->renderFile(
            'core/wp-config.php.twig',
            $configFile,
            $configParameters
        );

        $htaccessFile = $root . '/.htaccess';

        # Render htaccess file
        $this->renderFile(
            'core/htaccess.twig',
            $htaccessFile,
            $configParameters
        );
    }
}
