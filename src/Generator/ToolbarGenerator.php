<?php

/**
 * @file
 * Contains \WP\Console\Generator\ToolbarGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Extension\Manager;
use WP\Console\Core\Generator\Generator;
use WP\Console\Utils\Site;

/**
 * Class ToolbarGenerator
 *
 * @package WP\Console\Generator
 */
class ToolbarGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * ToolbarGenerator constructor.
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
        $plugin = $parameters['plugin'];

        $pluginFile = $this->extensionManager->getPlugin($plugin)->getPathname();
        $dir = $this->extensionManager->getPlugin($plugin)->getPath();


        $parameters = array_merge(
            $parameters, [
            "admin_toolbar_path" => 'admin/partials/toolbars-admin.php',
            "file_exists" => file_exists($pluginFile),
            "command_name" => 'toolbar'
            ]
        );

        $file_path_admin = $dir.'/'.$parameters['admin_toolbar_path'];
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

            if (function_exists($parameters['function_name'])) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the sidebar , The function name already exist at "%s"',
                        realpath($file_path_admin)
                    )
                );
            }
        }

        $this->renderFile(
            'plugin/src/Toolbar/toolbar.php.twig',
            $file_path_admin,
            $parameters,
            FILE_APPEND
        );
    }
}
