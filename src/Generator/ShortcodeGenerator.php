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
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $tag = $parameters['tag'];
        $class = $parameters['class_name'];
        $pluginFile = $parameters['pluginFile'];
        $pluginCamelCaseMachineName = $parameters['pluginCamelCaseMachineName'];
        $pluginPath = $parameters['pluginPath'];

        if (file_exists($pluginPath)) {
            if (!is_writable($pluginPath)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the shortcode as the target directory "%s" is not writable.',
                        realpath($pluginPath)
                    )
                );
            }
        }

        unset($parameters['pluginCamelCaseMachineName']);
        unset($parameters['pluginPath']);
        unset($parameters['class_name']);
        unset($parameters['pluginFile']);

        $parameters = array_merge(
            $parameters, [
            'plugin_shortcode_function' => $pluginCamelCaseMachineName . '_shortcode_' . $tag . '_init',
            'class_name' => $class . 'Shortcode',
            'class_name_path' => 'includes/' . $pluginCamelCaseMachineName . '-shortcode-' . $tag . '.php',
            'file_exists' => file_exists($pluginFile)
            ]
        );

        $this->renderFile(
            'plugin/includes/plugin-shortcode.php.twig',
            $pluginPath. '/' .  $parameters['class_name_path'],
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
