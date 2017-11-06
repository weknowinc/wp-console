<?php

/**
 * @file
 * Contains \WP\Console\Generator\UserContactMethodsGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Core\Generator\Generator;
use WP\Console\Extension\Manager;

/**
 * Class UserContactMethodsGenerator
 *
 * @package WP\Console\Generator
 */
class UserContactMethodsGenerator extends Generator
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
     * Generator UserContactMethods
     *
     * @param $plugin
     * @param $function_name
     * @param $methods_items
     * @param $site
     */
    public function generate(
        $plugin,
        $function_name,
        $methods_items,
        $site
    ) {
        $pluginFile = $this->extensionManager->getPlugin($plugin)->getPathname();
        $dir = $this->extensionManager->getPlugin($plugin)->getPath();

        $parameters = [
            "plugin" => $plugin,
            "function_name" => $function_name,
            "methods_items" => $methods_items,
            "admin_user_contact_methods_path" => 'admin/partials/userContactMethods-admin.php',
            "file_exists" => file_exists($pluginFile),
            "command_name" => 'userContactMethods'
        ];

        $file_path_admin = $dir.'/'.$parameters['admin_user_contact_methods_path'];
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
                        'Unable to generate the user_contactmethods , The function name already exist at "%s"',
                        realpath($file_path_admin)
                    )
                );
            }
        }

        $this->renderFile(
            'plugin/src/UserContactMethods/user-contactmethods.php.twig',
            $file_path_admin,
            $parameters,
            FILE_APPEND
        );
    }
}
