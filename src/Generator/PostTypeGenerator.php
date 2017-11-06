<?php

/**
 * @file
 * Contains \WP\Console\Generator\PostTypeGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Core\Generator\Generator;
use WP\Console\Extension\Manager;

/**
 * Class PostTypeGenerator
 *
 * @package WP\Console\Generator
 */
class PostTypeGenerator extends Generator
{

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PortTypeGenerator constructor.
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
     * @param string $plugin
     * @param string $class_name
     * @param string $function_name
     * @param string $post_type_key
     * @param string $description
     * @param string $name_singular
     * @param string $name_plural
     * @param string $taxonomy
     * @param boolean $hierarchical
     * @param boolean $exclude_from_search
     * @param boolean $enable_export
     * @param boolean $enable_archives
     * @param array $labels
     * @param array $supports
     * @param array $visibility
     * @param boolean $permalinks
     * @param array $capabilities
     * @param array $rest
     * @param boolean $child_themes
     */
    public function generate(
        $plugin,
        $class_name,
        $function_name,
        $post_type_key,
        $description,
        $name_singular,
        $name_plural,
        $taxonomy,
        $hierarchical,
        $exclude_from_search,
        $enable_export,
        $enable_archives,
        $labels,
        $supports,
        $visibility,
        $permalinks,
        $capabilities,
        $rest,
        $child_themes
    )
    {
        $pluginFile = $this->extensionManager->getPlugin($plugin)->getPathname();
        $dir = $this->extensionManager->getPlugin($plugin)->getPath();

        $parameters = [
            "plugin" => $plugin,
            "class_name" => $class_name,
            "function_name" => $function_name,
            "post_type_key" => $post_type_key,
            "description" => $description,
            "name_singular" => $name_singular,
            "name_plural" => $name_plural,
            "taxonomy" => $taxonomy,
            "hierarchical" => $hierarchical,
            "exclude_from_search" => $exclude_from_search,
            "enable_export" => $enable_export,
            "enable_archives" => $enable_archives,
            "labels" => $labels,
            "supports" => $supports,
            "visibility" => $visibility,
            "permalinks" => $permalinks,
            "capabilities" => $capabilities,
            "rest" => $rest,
            "child_theme" => $child_themes,
            "class_name_path" => 'PostType/' . lcfirst($class_name) . '.php',
            "admin_post_type_path" => 'admin/partials/post-types-admin.php',
            "file_exists" => file_exists($pluginFile),
            "command_name" => 'post_type'
        ];

        $file_path = $dir.'/admin/partials/'.$parameters['class_name_path'];
        $file_path_admin = $dir.'/'.$parameters['admin_post_type_path'];

        if (file_exists($file_path)) {
            if (!is_dir($file_path)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the post_type , it already exist at "%s"',
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
            'plugin/src/PostType/class-post-type.php.twig',
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