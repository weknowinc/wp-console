<?php

/**
 * @file
 * Contains \WP\Console\Generator\ToolbarGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Extension\Manager;
use WP\Console\Core\Utils\TranslatorManager;
use WP\Console\Core\Generator\Generator;

/**
 * Class ToolbarGenerator
 *
 * @package WP\Console\Generator
 */
class ToolbarGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var TranslatorManager
     */
    protected $translatorManager;

    /**
     * ToolbarGenerator constructor.
     *
     * @param Manager                    $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * Generate.
     * @param string  $plugin
     * @param string  $class_name
     * @param string  $menu
     */
    public function generate(
        $plugin,
        $class_name,
        $menu
    )
    {
        $pluginFile = $this->extensionManager->getPlugin($plugin)->getPathname();
        $dir = $this->extensionManager->getPlugin($plugin)->getPath(). '/admin/'.$class_name.'.php';

        $parameters = [
            'plugin' => $plugin,
            'class_name_toolbar' => $class_name,
            'menu' => $menu,
            'class_name_toolbar_path' => 'admin/'.$class_name.'.php',
            'file_exists' => file_exists($pluginFile),
            'toolbar_file_exists' => file_exists($dir)
        ];

        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the toolbar , it already exist at "%s"',
                        realpath($dir)
                    )
                );
            }
        }

        if($dir) {
            $this->renderFile(
                'plugin/plugin.php.twig',
                $pluginFile,
                $parameters,
                FILE_APPEND
            );
        }
        $this->renderFile(
            'plugin/src/Toolbar/toolbar.php.twig',
            $dir,
            $parameters
        );
    }
}
