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
     * @param $wp_nonce
     * @param $auto_save
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
        $fields_metabox,
        $wp_nonce,
        $auto_save
    ) {
        $parameters = [
            'plugin' => $plugin,
            'metabox_class_name' => $class_name,
            'metabox_id' => $metabox_id,
            'title' => $title,
            'callback_function' => $callback_function,
            'screen' => $screen,
            'page_location' => $page_location,
            'priority' => $priority,
            'fields_metabox' => $fields_metabox,
            'wp_nonce' => $wp_nonce,
            'auto_save' => $auto_save
        ];
        
        $file = $this->extensionManager->getPlugin($plugin)->getPath().'/admin/'.$class_name.'_meta_box.php';
        if (file_exists($file)) {
            if (!is_dir($file)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the metaboxs , it already exist at "%s"',
                        realpath($file)
                    )
                );
            }
        }
        
        $this->renderFile(
            'plugin/src/metabox/class-metabox.php.twig',
            $file,
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
