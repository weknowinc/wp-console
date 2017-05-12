<?php

namespace WP\Console\Utils;

use WP\Console\Annotations\WPCommandAnnotationReader;
use WP\Console\Extension\Manager;

/**
 * Class AnnotationValidator
 *
 * @package WP\Console\Utils
 */
class AnnotationValidator
{
    /**
     * @var WPCommandAnnotationReader
     */
    protected $annotationCommandReader;
    /**
     * @var Manager
     */
    protected $extensionManager;
    /**
     * @var array
     */
    private $extensions = [];

    /**
     * AnnotationValidator constructor.
     *
     * @param WPCommandAnnotationReader $annotationCommandReader
     * @param Manager                       $extensionManager
     */
    public function __construct(
        WPCommandAnnotationReader $annotationCommandReader,
        Manager $extensionManager
    ) {
        $this->annotationCommandReader = $annotationCommandReader;
        $this->extensionManager = $extensionManager;
    }

    /**
     * @param $class
     * @return bool
     */
    public function isValidCommand($class)
    {
        $annotation = $this->annotationCommandReader->readAnnotation($class);
        if (!$annotation) {
            return true;
        }

        $dependencies = $this->extractDependencies($annotation);

        if (!$dependencies) {
            return true;
        }

        foreach ($dependencies as $dependency) {
            if (!$this->isExtensionInstalled($dependency)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $extension
     * @return bool
     */
    protected function isExtensionInstalled($extension)
    {
        if (!$this->extensions) {
            $modules = $this->extensionManager->discoverPlugins()
                ->showCore()
                ->showNoCore()
                ->showActivated()
                ->getList(true);

            $themes = $this->extensionManager->discoverThemes()
                ->showCore()
                ->showNoCore()
                ->showActivated()
                ->getList(true);

            $this->extensions = array_merge(
                $modules,
                $themes
            );
        }

        return in_array($extension, $this->extensions);
    }

    /**
     * @param $annotation
     * @return array
     */
    protected function extractDependencies($annotation)
    {
        $dependencies = [];
        if (array_key_exists('extension', $annotation)) {
            $dependencies[] = $annotation['extension'];
        }
        if (array_key_exists('dependencies', $annotation)) {
            $dependencies = array_merge($dependencies, $annotation['dependencies']);
        }

        return $dependencies;
    }
}
