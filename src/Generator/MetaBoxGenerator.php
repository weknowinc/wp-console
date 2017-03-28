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
class MetaBoxGenerator extends Generator
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
     * @param $plugin,
     * @param $class_name
     * @param $metabox_id,
     * @param $title,
     * @param $callback_function,
     * @param $screen,
     * @param $page_location,
     * @param $priority,
     * @param $fields_metabox
     */
    public function generate(
        $plugin,
        $class_name,
        $metabox_id,
        $title,
        $callback_function,
        $screen,
        $page_location,
        $priority,
        $fields_metabox

    ) {
        $parameters = [
            'plugin' => $plugin,
            'class_name' => $class_name,
            'metabox_id' => $metabox_id,
            'title' => $title,
            'callback_function' => $callback_function,
            'screen' => $screen,
            'page_location' => $page_location,
            'priority' => $priority,
            'fields_metabox' => $fields_metabox
        ];
        
        $this->renderFile(
            'plugin/src/metabox/class-metabox.php.twig',
            $this->extensionManager->getPlugin($plugin)->getPath().'/admin/class_meta_box.php',
            $parameters
        );
    
        $this->renderFile(
            'plugin/src/metabox/class-metabox-display.php.twig',
            $this->extensionManager->getPlugin($plugin)->getPath().'/admin/class_meta_box_display.php',
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