<?php

/**
 * @file
 * Contains \WP\Console\Generator\MetaBoxGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Core\Generator\Generator;
use WP\Console\Extension\Manager;

/**
 * Class MetaBoxGenerator
 *
 * @package WP\Console\Generator
 */
class MetaBoxGenerator extends Generator
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
     * Generator MetaBox
     *
     * @param string $plugin,
     * @param string $class_name
     * @param string $metabox_id,
     * @param string $title,
     * @param string $callback_function,
     * @param string $screen,
     * @param string $page_location,
     * @param string $priority,
     * @param array $metabox_items
     * @param boolean $wp_nonce
     * @param boolean $auto_save
     */
    public function generate(
        $plugin,
        $class_name,
        $metabox_id,
        $title,
        $callback_function,
        $screen,
        $page_location,
        $priority,
        $metabox_items,
        $wp_nonce,
        $auto_save
    ) {
        $pluginFile = $this->extensionManager->getPlugin($plugin)->getPathname();
        $dir = $this->extensionManager->getPlugin($plugin)->getPath();

        $parameters = [
            "plugin" => $plugin,
            "class_name" => $class_name,
            "metabox_id" => $metabox_id,
            "title" => $title,
            "callback_function" => $callback_function,
            "screen" => $screen,
            "page_location" => $page_location,
            "priority" => $priority,
            "metabox_items" => $metabox_items,
            "wp_nonce" => $wp_nonce,
            "auto_save" => $auto_save,
            "class_name_path" => 'Metabox/' . lcfirst($class_name) . '.php',
            "admin_metabox_path" => 'admin/partials/metaboxes-admin.php',
            "file_exists" => file_exists($pluginFile),
            "command_name" => 'metabox'
        ];

        $file_path = $dir.'/admin/partials/'.$parameters['class_name_path'];
        $file_path_admin = $dir.'/'.$parameters['admin_metabox_path'];

        if (file_exists($file_path)) {
            if (!is_dir($file_path)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the metaboxes , it already exist at "%s"',
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
            'plugin/src/Metabox/class-metabox.php.twig',
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
