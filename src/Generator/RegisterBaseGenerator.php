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
class RegisterBaseGenerator extends Generator
{

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * RegisterStyleGenerator constructor.
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
     * @param string $extension_type
     * @param string $extension
     * @param string $type
     * @param string $function_name
     * @param string $hook
     * @param array  $register_items
     * @param Site   $site
     */
    public function generate(
        $extension_type,
        $extension,
        $type,
        $function_name,
        $hook,
        $register_items,
        $site
    ) {
        $extensionObject = $this->extensionManager->getWPExtension($extension_type, $extension);

        $parameters = [
            $extension_type => $extension,
            "type" => $type,
            "function_name" => $function_name,
            "hook" => $hook,
            "register_items" => $register_items,
            "admin_registers_path" => 'admin/partials/register-'.$type.'-admin.php',
            "file_exists" => file_exists($extensionObject->getPathName().($extension_type == "theme" ? '/functions.php':'')),
            "command_name" => 'registers'
        ];

        $file_path_admin = $extensionObject->getPath().'/'.$parameters['admin_registers_path'];
        $parameters['admin_file_exists'] = file_exists($file_path_admin);

        if (!file_exists($file_path_admin)) {
            $this->renderFile(
                $extension_type == "theme" ? 'theme/functions.php.twig': 'plugin/plugin.php.twig',
                $extensionObject->getPathname() . ($extension_type == "theme" ? '/functions.php':''),
                $parameters,
                FILE_APPEND
            );
        } else {
            $site->loadLegacyFile($file_path_admin);

            if (function_exists($function_name)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the register '.$type.' , The function name already exist at "%s"',
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
