<?php

/**
 * @file
 * Contains \WP\Console\Generator\WidgetGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Core\Generator\Generator;
use WP\Console\Extension\Manager;

/**
 * Class WidgetGenerator
 *
 * @package WP\Console\Generator
 */
class WidgetGenerator extends Generator
{

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * AuthenticationProviderGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }


    /**
     * Generator Widget
     *
     * @param $plugin
     * @param $class_name
     * @param $widget_id
     * @param $title
     * @param $description
     * @param $widget_class_name
     * @param $widget_fields
     */
    public function generate(
        $plugin,
        $class_name,
        $widget_id,
        $title,
        $description,
        $widget_class_name,
        $widget_fields

    ) {
        $pluginFile = $this->extensionManager->getPlugin($plugin)->getPathname();
        $dir = $this->extensionManager->getPlugin($plugin)->getPath();

        $parameters = [
            "plugin" => $plugin,
            "class_name" => $class_name,
            "widget_id" => $widget_id,
            "title" => $title,
            "description" => $description,
            "widget_class_name" => $widget_class_name,
            "widget_fields" => $widget_fields,
            "class_name_path" => 'Widget/' . lcfirst($class_name) . '.php',
            "admin_widget_path" => 'admin/partials/widgets-admin.php',
            "file_exists" => file_exists($pluginFile),
            "command_name" => 'widget'
        ];

        $file_path = $dir.'/admin/partials/'.$parameters['class_name_path'];
        $file_path_admin = $dir.'/'.$parameters['admin_widget_path'];

        if (file_exists($file_path)) {
            if (!is_dir($file_path)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the widgets , it already exist at "%s"',
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
            'plugin/src/Widget/class-widget.php.twig',
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
