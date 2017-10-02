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
class TaxonomyGenerator extends Generator
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
    )
    {
        $this->extensionManager = $extensionManager;
    }


    /**
     * Generator MetaBox
     *
     * @param $plugin
     * @param $class_name
     * @param $function_name
     * @param $taxonomy_key
     * @param $name_singular
     * @param $name_plural
     * @param $post_type
     * @param $hierarchical
     * @param $labels
     * @param $visibility
     * @param $permalinks
     * @param $capabilities
     * @param $rest
     * @param $child_themes
     * @param $update_count_callback
     */
    public function generate(
        $plugin,
        $class_name,
        $function_name,
        $taxonomy_key,
        $name_singular,
        $name_plural,
        $post_type,
        $hierarchical,
        $labels,
        $visibility,
        $permalinks,
        $capabilities,
        $rest,
        $child_themes,
        $update_count_callback
    )
    {
        $pluginFile = $this->extensionManager->getPlugin($plugin)->getPathname();
        $dir = $this->extensionManager->getPlugin($plugin)->getPath();

        $parameters = [
            "plugin" => $plugin,
            "class_name" => $class_name,
            "function_name" => $function_name,
            "taxonomy_key" => $taxonomy_key,
            "name_singular" => $name_singular,
            "name_plural" => $name_plural,
            "post_type" => $post_type,
            "hierarchical" => $hierarchical,
            "labels" => $labels,
            "visibility" => $visibility,
            "permalinks" => $permalinks,
            "capabilities" => $capabilities,
            "rest" => $rest,
            "child_theme" => $child_themes,
            "update_count_callback" => $update_count_callback,
            "class_name_path" => 'Taxonomy/' . lcfirst($class_name) . '.php',
            "admin_taxonomy_path" => 'admin/partials/taxonomies-admin.php',
            "file_exists" => file_exists($pluginFile),
            "command_name" => 'taxonomy'
        ];

        $file_path = $dir.'/admin/partials/'.$parameters['class_name_path'];
        $file_path_admin = $dir.'/'.$parameters['admin_taxonomy_path'];

        if (file_exists($file_path)) {
            if (!is_dir($file_path)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the taxonomy , it already exist at "%s"',
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
            'plugin/src/Taxonomy/class-taxonomy.php.twig',
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