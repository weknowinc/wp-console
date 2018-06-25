<?php

/**
 * @file
 * Contains \WP\Console\Generator\ThemeGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Core\Generator\Generator;
use WP\Console\Utils\Site;

/**
 * Class ThemeGenerator
 *
 * @package WP\Console\Generator
 */
class ThemeGenerator extends Generator
{
    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters, $templateFiles, $screenshot)
    {
        $machineName = $parameters['machine_name'];
        $themePath = $parameters['themePath'];

        $dir = ($themePath == "/" ? '': $themePath).'/'.$machineName;

        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the theme as the target directory "%s" exists but is a file.',
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

        unset($parameters['themePath']);

        if (!empty($templateFiles)) {
            foreach ($templateFiles as $template) {
                $this->renderFile(
                    'theme/template.php.twig',
                    $dir.'/'.$template.'.php',
                    ['template' => $template, 'theme' => $parameters['theme'], 'package' => $parameters['package']]
                );
            }
        }

        $this->renderFile(
            'theme/style.css.twig',
            $dir.'/style.css',
            $parameters
        );

        $this->renderFile(
            'theme/index.php.twig',
            $dir.'/index.php',
            $parameters
        );

        if (file_exists($screenshot)) {
            $file = explode(".", $screenshot);
            copy($screenshot, $dir.'/screenshot.'.end($file));
        }
    }
}
