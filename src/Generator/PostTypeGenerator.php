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
     * @param $plugin
     * @param $class_name
     * @param $function_name
     * @param $post_type_key
     * @param $description
     * @param $name_singular
     * @param $name_plural
     * @param $taxonomy
     * @param $hierarchical
     * @param $exclude_from_search
     * @param $enable_export
     * @param $enable_archives
     * @param $labels
     * @param $supports
     * @param $visibility
     * @param $permalinks
     * @param $capabilities
     * @param $rest
     * @param $child_themes
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

        $parameters = [
            'plugin' => $plugin,
            'class_name_post_type' => $class_name,
            'function_name' => $function_name,
            'post_type_key' => $post_type_key,
            'description' => $description,
            'name_singular' => $name_singular,
            'name_plural' => $name_plural,
            'taxonomy' => $taxonomy,
            'hierarchical' => $hierarchical,
            'exclude_from_search' => $exclude_from_search,
            'enable_export' => $enable_export,
            'enable_archives' => $enable_archives,
            'labels' => $labels,
            'supports' => $supports,
            'visibility' => $visibility,
            'permalinks' => $permalinks,
            'capabilities' => $capabilities,
            'rest' => $rest,
            'child_theme' => $child_themes,
            'class_name_post_type_path' => 'admin/' . lcfirst($class_name) . '-post-type.php',
            'file_exists' => file_exists($pluginFile)
        ];

        $dir = $this->extensionManager->getPlugin($plugin)->getPath(). '/' . $parameters['class_name_post_type_path'];

        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the metaboxs , it already exist at "%s"',
                        realpath($dir)
                    )
                );
            }
        }

        $this->renderFile(
            'plugin/src/PostType/class-post-type.php.twig',
            $dir,
            $parameters
        );

        $this->renderFile(
            'plugin/plugin.php.twig',
            $pluginFile,
            $parameters,
            FILE_APPEND
        );
    }
}