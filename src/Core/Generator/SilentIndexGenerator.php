<?php

/**
 * @file
 * Contains WP\Console\Core\Generator\SilentIndenxGenerator.
 */
namespace WP\Console\Core\Generator;

/**
 * Class SilentIndenxGenerator
 * @package WP\Console\Core\Generator
 */
class SilentIndexGenerator extends Generator
{

    /**
     * @param string $root
     * @param string $destination
     */
    public function generate(
        $root,
        $destination
    ) {

        $indexFile = $root . DIRECTORY_SEPARATOR . $destination . DIRECTORY_SEPARATOR . 'index.php';
        $this->renderFile(
            'core/index-silent.php.twig',
            $indexFile,
            []
        );
    }
}
