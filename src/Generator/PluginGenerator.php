<?php

/**
 * @file
 * Contains \WP\Console\Generator\PluginGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Core\Generator\Generator;

/**
 * Class PluginGenerator
 *
 * @package WP\Console\Generator
 */
class PluginGenerator extends Generator
{

    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $machineName = $parameters['machine_name'];
        $class = $parameters['class_name_base'];
        $activate = $parameters['activate'];
        $deactivate = $parameters['deactivate'];
        $uninstall = $parameters['uninstall'];
        $pluginPath = $parameters['plugin_path'];

        $dir = ($pluginPath == "/" ? '': $pluginPath).'/'.$machineName;
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the plugin as the target directory "%s" exists but is a file.',
                        realpath($dir)
                    )
                );
            }
            $files = scandir($dir);
            if ($files != ['.', '..']) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the module as the target directory "%s" is not empty.',
                        realpath($dir)
                    )
                );
            }
            if (!is_writable($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the module as the target directory "%s" is not writable.',
                        realpath($dir)
                    )
                );
            }
        }

        $parameters = array_merge(
            $parameters, [
            'class_name_activator' => $class . 'Activator',
            'class_name_activator_path' => 'includes/' . $machineName . '-activator.php',
            'class_name_deactivator' => $class . 'Deactivator',
            'class_name_deactivator_path' => 'includes/' . $machineName . '-deactivator.php',
            'file_exists' => false
            ]
        );

        unset($parameters['uninstall']);

        $this->renderFile(
            'plugin/plugin.php.twig',
            $dir.'/'.$machineName.'.php',
            $parameters
        );

        $this->renderFile(
            'plugin/readme.txt.twig',
            $dir.'/readme.txt',
            $parameters
        );

        if ($activate) {
            $this->renderFile(
                'plugin/includes/plugin-activator.php.twig',
                $dir. '/' . $parameters['class_name_activator_path'],
                $parameters
            );
        }

        if ($deactivate) {
            $this->renderFile(
                'plugin/includes/plugin-deactivator.php.twig',
                $dir. '/' .  $parameters['class_name_deactivator_path'],
                $parameters
            );
        }

        if ($uninstall) {
            $this->renderFile(
                'plugin/uninstall.php.twig',
                $dir. '/uninstall.php',
                $parameters
            );
        }
    }
}
