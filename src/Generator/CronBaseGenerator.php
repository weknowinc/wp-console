<?php

/**
 * @file
 * Contains \WP\Console\Generator\CronBaseGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Core\Generator\Generator;
use WP\Console\Extension\Manager;

/**
 * Class CronBaseGenerator
 *
 * @package WP\Console\Generator
 */
class CronBaseGenerator extends Generator
{

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * CronBaseGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }


    /**
     * Generator CronBase
     *
     * @param string $plugin
     * @param string $class_name
     * @param string $timestamp,
     * @pqram string $recurrence,
     * @param string $hook_name,
     * @param string $hook_arguments,
     * @param string $type
     */
    public function generate(
        $plugin,
        $class_name,
        $timestamp,
        $recurrence,
        $hook_name,
        $hook_arguments,
        $type
    ) {
        $pluginFile = $this->extensionManager->getPlugin($plugin)->getPathname();
        $dir = $this->extensionManager->getPlugin($plugin)->getPath();

        $parameters = [
            "plugin" => $plugin,
            "class_name" => $class_name,
            "timestamp" => $timestamp,
            "recurrence" => $recurrence,
            "hook_name" => $hook_name,
            "hook_arguments" => $hook_arguments,
            "class_name_path" => 'Cron'.ucfirst($type).'/' . lcfirst($class_name) . '.php',
            "admin_cron_path" => 'admin/partials/cron-'.$type.'-admin.php',
            "file_exists" => file_exists($pluginFile),
            "command_name" => 'cron'.$type,
            "type" => $type
        ];

        $file_path = $dir.'/admin/partials/'.$parameters['class_name_path'];
        $file_path_admin = $dir.'/'.$parameters['admin_cron_path'];
        $parameters['admin_file_exists'] = file_exists($file_path_admin);

        if (file_exists($file_path)) {
            if (!is_dir($file_path)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the '.$type.' cron, it already exist at "%s"',
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
            'plugin/src/Cron/class-cron-'.$type.'.php.twig',
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
