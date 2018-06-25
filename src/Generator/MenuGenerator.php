<?php

/**
 * @file
 * Contains \WP\Console\Generator\MenuGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Extension\Manager;
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
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $plugin = $parameters['plugin'];
        $class = $parameters['class_name'];

        $pluginFile = $this->extensionManager->getPlugin($plugin)->getPathname();
        $dir = $this->extensionManager->getPlugin($plugin)->getPath();

        $parameters = array_merge(
            $parameters, [
            'class_name_path' => 'Menu/' . lcfirst($class) . '.php',
            'admin_menu_path' => 'admin/partials/menus-admin.php',
            'file_exists' => file_exists($pluginFile),
            "command_name" => 'menu'
            ]
        );

        $file_path = $dir.'/admin/partials/'.$parameters['class_name_path'];
        $file_path_admin = $dir.'/'.$parameters['admin_menu_path'];

        if (file_exists($file_path)) {
            if (!is_dir($file_path)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the menu , it already exist at "%s"',
                        realpath($file_path)
                    )
                );
            }
        }

        if (!file_exists($file_path_admin)) {
            $this->renderFile(
                'plugin/plugin.php.twig',
                $pluginFile,
                $parameters,
                FILE_APPEND
            );
        }

        $this->renderFile(
            'plugin/src/Menu/class-menu.php.twig',
            $file_path,
            $parameters
        );

        $parameters['admin_file_exists'] = file_exists($file_path_admin);

        $this->renderFile(
            'plugin/src/class-admin.php.twig',
            $file_path_admin,
            $parameters,
            FILE_APPEND
        );
    }
}
