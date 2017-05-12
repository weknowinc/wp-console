<?php
/**
 * @file
 * Contains \WP\Console\Annotations\WPCommand.
 */

namespace WP\Console\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */

class WPCommand
{
    /**
     * @var string
     */
    public $extension;

    /**
     * @var string
     */
    public $extensionType;

    /**
     * @var array
     */
    public $dependencies;
}
