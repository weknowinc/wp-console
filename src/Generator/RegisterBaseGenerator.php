<?php

/**
 * @file
 * Contains \WP\Console\Generator\RegisterStyleGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Core\Generator\Generator;
use WP\Console\Extension\Manager;
use WP\Console\Utils\Site;

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
     * {@inheritdoc}
     */
    public function generate(array $parameters, Site $site)
    {
        $extensionType = $parameters['extension_type'];
        $extension = $parameters['extension'];

        unset($parameters['extension_type']);
        unset($parameters['extension']);

        $extensionObject = $this->extensionManager->getWPExtension($extensionType, $extension);

        $parameters = array_merge(
            $parameters, [
            $extensionType => $extension,
            "admin_registers_path" => 'admin/partials/register-'.$parameters['type'].'-admin.php',
            "file_exists" => file_exists($extensionObject->getPathName().($extensionType == "theme" ? '/functions.php':'')),
            "command_name" => 'registers'
            ]
        );

        $file_path_admin = $extensionObject->getPath().'/'.$parameters['admin_registers_path'];
        $parameters['admin_file_exists'] = file_exists($file_path_admin);

        if (!file_exists($file_path_admin)) {
            $this->renderFile(
                $extensionType == "theme" ? 'theme/functions.php.twig': 'plugin/plugin.php.twig',
                $extensionObject->getPathname() . ($extensionType == "theme" ? '/functions.php':''),
                $parameters,
                FILE_APPEND
            );
        } else {
            $site->loadLegacyFile($file_path_admin);

            if (function_exists($parameters['function_name'])) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the register '.$parameters['type'].' , The function name already exist at "%s"',
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
