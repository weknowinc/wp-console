<?php

/**
 * @file
 * Contains \WP\Console\Generator\SettingsPageGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Core\Generator\Generator;
use WP\Console\Extension\Manager;

/**
 * Class SettingsPageGenerator
 *
 * @package WP\Console\Generator
 */
class SettingsPageGenerator extends Generator
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
     * Generator SettingsPage
     *
     * @param string $plugin,
     * @param string $class_name
     * @param string $setting_group
     * @param string $setting_name
     * @param string $page_title
     * @param string $menu_title
     * @param string $capability
     * @param string $slug
     * @param string $callback_function
     * @param array  $sections
     * @param array  $fields
     * @param string $text_domain
     */
    public function generate(
        $plugin,
        $class_name,
        $setting_group,
        $setting_name,
        $page_title,
        $menu_title,
        $capability,
        $slug,
        $callback_function,
        $sections,
        $fields,
        $text_domain
    ) {
        $pluginFile = $this->extensionManager->getPlugin($plugin)->getPathname();
        $dir = $this->extensionManager->getPlugin($plugin)->getPath();

        $parameters = [
            "plugin" => $plugin,
            "class_name" => $class_name,
            "setting_group" => $setting_group,
            "setting_name" => $setting_name,
            "page_title" => $page_title,
            "menu_title" => $menu_title,
            "capability" => $capability,
            "slug" => $slug,
            "callback_function" => $callback_function,
            "sections" => $sections,
            "fields" => $fields,
            "text_domain" => $text_domain,
            "class_name_path" => 'SettingsPage/' . lcfirst($class_name) . '.php',
            "admin_settings_page_path" => 'admin/partials/settings-page-admin.php',
            "file_exists" => file_exists($pluginFile),
            "command_name" => 'settingspage'
        ];

        $file_path = $dir.'/admin/partials/'.$parameters['class_name_path'];
        $file_path_admin = $dir.'/'.$parameters['admin_settings_page_path'];

        if (file_exists($file_path)) {
            if (!is_dir($file_path)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the settings page , it already exist at "%s"',
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
            'plugin/src/SettingsPage/class-settings-page.php.twig',
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
