<?php

/**
 * @file
 * Contains \WP\Console\Generator\MenuGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Extension\Manager;
use WP\Console\Core\Utils\TranslatorManager;
use WP\Console\Core\Generator\Generator;

/**
 * Class MenuGenerator
 *
 * @package WP\Console\Generator
 */
class MenuGenerator extends Generator
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
     * MenuGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * Generate.
     *
     * @param string  $extension
     * @param string  $class_name
     * @param string  $function_name
     * @param string  $menu_items
     * @param string  $description
     * @param boolean $child_themes
     */
    public function generate(
        $plugin,
        $class_name,
        $function_name,
        $menu_items,
        $child_themes
    ) {
        $pluginFile = $this->extensionManager->getPlugin($plugin)->getPathname();

        $parameters = [
            'plugin' => $plugin,
            'class_name_menu' => $class_name,
            'function_name' => $function_name,
            'menu_items' => $menu_items,
            'child_theme' => $child_themes,
            'class_name_menu_path' => 'admin/' . lcfirst($class_name) . '-menu.php',
            'file_exists' => file_exists($pluginFile)
        ];

        $dir = $this->extensionManager->getPlugin($plugin)->getPath(). '/' . $parameters['class_name_menu_path'];

        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the menu , it already exist at "%s"',
                        realpath($dir)
                    )
                );
            }
        }

        $this->renderFile(
            'plugin/src/Menu/menu.php.twig',
            $dir,
            $parameters
        );

        $this->renderFile(
            'plugin/plugin.php.twig',
            $pluginFile,
            $parameters,
            FILE_APPEND
        );
    }
}
