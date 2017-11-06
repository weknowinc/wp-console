<?php

/**
 * @file
 * Contains \WP\Console\Generator\ShortcodeGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Core\Generator\Generator;

/**
 * Class PluginGenerator
 *
 * @package WP\Console\Generator
 */
class ShortcodeGenerator extends Generator
{
    /**
     * @param Site        $site
     * @param string $plugin
     * @param string $machineName
     * @param string $dir
     * @param string $description
     * @param string $author
     * @param string $authorUrl
     * @param boolean $test
     */
    public function generate(
        $tag,
        $plugin,
        $dir,
        $pluginNameSpace,
        $pluginCamelCaseMachineName,
        $className,
        $pluginFile
    ) {

        if (file_exists($dir)) {
            if (!is_writable($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the shortcode as the target directory "%s" is not writable.',
                        realpath($dir)
                    )
                );
            }
        }

        $parameters = [
            'tag' => $tag,
            'plugin' => $plugin,
            'pluginNameSpace' => $pluginNameSpace,
            'plugin_shortcode_function' => $pluginCamelCaseMachineName . '_shortcode_' . $tag . '_init',
            'class_name' => $className . 'Shortcode',
            'class_name_path' => 'includes/' . $pluginCamelCaseMachineName . '-shortcode-' . $tag . '.php',
            'file_exists' => file_exists($pluginFile)
        ];

        $this->renderFile(
            'plugin/includes/plugin-shortcode.php.twig',
            $dir. '/' .  $parameters['class_name_path'],
            $parameters
        );


        // Add init function to register shortcode
        $this->renderFile(
            'plugin/shortcode-init.php.twig',
            $pluginFile,
            $parameters,
            FILE_APPEND
        );

    }
}
