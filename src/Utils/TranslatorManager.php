<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\TranslatorManager.
 */

namespace WP\Console\Utils;

use WP\Console\Core\Utils\TranslatorManager as TranslatorManagerBase;
use WP\Console\Extension\Manager;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Finder\Finder;

/**
 * Class TranslatorManager
 *
 * @package WP\Console\Utils
 */
class TranslatorManager extends TranslatorManagerBase
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * TranslatorManager constructor.
     *
     * @param Manager                       $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        parent::__construct();
        $this->extensionManager = $extensionManager;
    }

    /**
     * @param $extensionPath
     */
    private function addResourceTranslationsByExtensionPath($extensionPath)
    {
        $languageDirectory = sprintf(
            '%s/console/translations/%s',
            $extensionPath,
            $this->language
        );

        if (!is_dir($languageDirectory)) {
            return;
        }
        $finder = new Finder();
        $finder->files()
            ->name('*.yml')
            ->in($languageDirectory);
        foreach ($finder as $file) {
            $resource = $languageDirectory . '/' . $file->getBasename();
            $filename = $file->getBasename('.yml');
            $key = 'commands.' . $filename;
            try {
                $this->loadTranslationByFile($resource, $key);
            } catch (ParseException $e) {
                echo $key . '.yml ' . $e->getMessage();
            }
        }
    }

    /**
     * @param $module
     */
    private function addResourceTranslationsByPlugin($plugin)
    {
        $extensionPath = $this->extensionManager->getPlugin($plugin)->getPath();
        $this->addResourceTranslationsByExtensionPath(
            $extensionPath
        );
    }

    /**
     * @param $theme
     */
    private function addResourceTranslationsByTheme($theme)
    {
        $extensionPath = $this->extensionManager->getTheme($theme)->getPath();
        $this->addResourceTranslationsByExtensionPath(
            $extensionPath
        );
    }

    /**
     * @param $extension
     * @param $type
     */
    public function addResourceTranslationsByExtension($extension, $type)
    {
        if ($type == 'plugin') {
            $this->addResourceTranslationsByPlugin($extension);
            return;
        }
        if ($type == 'theme') {
            $this->addResourceTranslationsByTheme($extension);
            return;
        }
    }
}
