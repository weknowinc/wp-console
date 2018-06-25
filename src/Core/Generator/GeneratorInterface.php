<?php

/**
 * @file
 * Contains \WP\Console\Core\Generator\Generator.
 */

namespace WP\Console\Core\Generator;

/**
 * Class Generator
 *
 * @package WP\Console\Core\GeneratorInterface
 */
interface GeneratorInterface
{

    /**
     * @param array $parameters
     * @return void
     */
    public function generate(array $parameters);
}
