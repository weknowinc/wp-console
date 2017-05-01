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
    ) {
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
    
    ) {
        $parameters = [
            'plugin' => $plugin,
            'class_name_taxonomy' => $class_name,
            'function_name' => $function_name,
            'taxonomy_key' => $taxonomy_key,
            'name_singular' => $name_singular,
            'name_plural' => $name_plural,
            'post_type' => $post_type,
            'hierarchical' => $hierarchical,
            'labels' => $labels,
            'visibility' => $visibility,
            'permalinks' => $permalinks,
            'capabilities' => $capabilities,
            'rest' => $rest,
            'child_theme' => $child_themes,
            'update_count_callback' => $update_count_callback
        ];

        $this->renderFile(
            'plugin/src/taxonomy/class-taxonomy.php.twig',
            $this->extensionManager->getPlugin($plugin)->getPath().'/admin/'.$class_name.'_taxonomy.php',
            $parameters
        );
        
        $this->renderFile(
            'plugin/plugin.php.twig',
            $this->extensionManager->getPlugin($plugin)->getPathname(),
            $parameters,
            FILE_APPEND
        );
    }
}