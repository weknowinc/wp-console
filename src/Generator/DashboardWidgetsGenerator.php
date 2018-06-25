<?php

/**
 * @file
 * Contains \WP\Console\Generator\DashboardWidgetsGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Core\Generator\Generator;
use WP\Console\Extension\Manager;

/**
 * Class DashboardWidgetsGenerator
 *
 * @package WP\Console\Generator
 */
class DashboardWidgetsGenerator extends Generator
{
    
    /**
     * @var Manager
     */
    protected $extensionManager;
    
    /**
     * DashboardWidgetsGenerator constructor.
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
            "class_name_path" => 'DashboardWidgets/' . lcfirst($class) . '.php',
            "admin_dashboard_widgets_path" => 'admin/partials/dashboard-widgets-admin.php',
            "file_exists" => file_exists($pluginFile),
            "command_name" => 'dashboard_widgets'
            ]
        );

        $file_path = $dir.'/admin/partials/'.$parameters['class_name_path'];
        $file_path_admin = $dir.'/'.$parameters['admin_dashboard_widgets_path'];
        $parameters['admin_file_exists'] = file_exists($file_path_admin);

        if (file_exists($file_path)) {
            if (!is_dir($file_path)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate dashboard widgets , it already exist at "%s"',
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
            'plugin/src/Widget/class-dashboard-widgets.php.twig',
            $file_path,
            $parameters
        );

        $this->renderFile(
            'plugin/src/class-admin.php.twig',
            $file_path_admin,
            $parameters,
            FILE_APPEND
        );
    }
}
