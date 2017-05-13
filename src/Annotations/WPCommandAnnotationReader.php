<?php

namespace WP\Console\Annotations;

use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Class WPCommandReader
 *
 * @package WP\Console\Annotations
 */
class WPCommandAnnotationReader
{
    /**
     * @param $class
     * @return array
     */
    public function readAnnotation($class)
    {
        $annotation = [];
        $reader = new AnnotationReader();

        $wpCommandAnnotation = $reader->getClassAnnotation(
            new \ReflectionClass($class),
            'WP\\Console\\Annotations\\WPCommand'
        );

        if ($wpCommandAnnotation) {
            $annotation['extension'] = $wpCommandAnnotation->extension?:'';
            $annotation['extensionType'] = $wpCommandAnnotation->extensionType?:'';
            $annotation['dependencies'] = $wpCommandAnnotation->dependencies?:[];
        }

        return $annotation;
    }
}
