<?php

/**
 * @file
 * Contains \WP\Console\Generator\RegisterStyleGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Core\Generator\Generator;
use WP\Console\Extension\Manager;

/**
 * Class RegisterStyleGenerator
 *
 * @package WP\Console\Generator
 */
class RegisterStyleGenerator extends Generator
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
     * Generator RegisterStyle
     *
     * @param string $plugin
     * @param string $function_name
     * @param string $hook
     * @param array  $styles_items
     * @param Site   $site
     */
    public function generate(
        $plugin,
        $function_name,
        $hook,
        $styles_items,
        $site
    ) {
        $pluginFile = $this->extensionManager->getPlugin($plugin)->getPathname();
        $dir = $this->extensionManager->getPlugin($plugin)->getPath();

        $parameters = [
            "plugin" => $plugin,
            "function_name" => $function_name,
            "hook" => $hook,
            "styles_items" => $styles_items,
            "admin_register_style_path" => 'admin/partials/register-styles-admin.php',
            "file_exists" => file_exists($pluginFile),
            "command_name" => 'register_styles'
        ];

        $file_path_admin = $dir.'/'.$parameters['admin_register_style_path'];
        $parameters['admin_file_exists'] = file_exists($file_path_admin);

        if (!file_exists($file_path_admin)) {
            $this->renderFile(
                'plugin/plugin.php.twig',
                $pluginFile,
                $parameters,
                FILE_APPEND
            );
        } else {
            $site->loadLegacyFile($file_path_admin);

            if (function_exists($function_name)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the register styles , The function name already exist at "%s"',
                        realpath($file_path_admin)
                    )
                );
            }
        }

        $this->renderFile(
            'plugin/src/RegisterStyle/register-styles.php.twig',
            $file_path_admin,
            $parameters,
            FILE_APPEND
        );
    }
}
