<?php

/**
 * @file
 * Contains \WP\Console\Generator\PluginGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Core\Generator\Generator;
use WP\Console\Utils\Site;

/**
 * Class PluginGenerator
 *
 * @package WP\Console\Generator
 */
class PluginGenerator extends Generator
{
    /**
     * @param Site $site
     * @param $plugin
     * @param $machineName
     * @param $dir
     * @param $description
     * @param $author
     * @param $authorUrl
     * @param $test
     */
    public function generate(
        $site,
        $plugin,
        $machineName,
        $dir,
        $description,
        $author,
        $authorUrl,
        $package,
        $className,
        $activate,
        $deactivate
    ) {
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

        $parameters = [
            'plugin' => $plugin,
            'plugin_uri' => '',
            'machine_name' => $machineName,
            'type' => 'module',
            'version' => $site->getBlogInfo('version'),
            'description' => $description,
            'author' => $author,
            'author_uri' => $authorUrl,
            'package' => $package,
            'class_name_base' => $className,
            'class_name_activator' => $className . 'Activator',
            'class_name_activator_path' => 'includes/' . $machineName . '-activator.php',
            'class_name_deactivator' => $className . 'Deactivator',
            'class_name_deactivator_path' => 'includes/' . $machineName . '-deactivator.php',
            'activate' => $activate,
            'deactivate' => $deactivate
        ];

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

        if($activate) {
            $this->renderFile(
                'plugin/includes/plugin-activator.php.twig',
                $dir. '/' . $parameters['class_name_activator_path'],
                $parameters
            );
        }

        if($deactivate) {
            $this->renderFile(
                'plugin/includes/plugin-deactivator.php.twig',
                $dir. '/' .  $parameters['class_name_deactivator_path'],
                $parameters
            );
        }
    }
}
