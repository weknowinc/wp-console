<?php

/**
 * @file
 * Contains \WP\Console\Generator\QuickTagGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Extension\Manager;
use WP\Console\Core\Utils\TranslatorManager;
use WP\Console\Core\Generator\Generator;

/**
 * Class QuickTagGenerator
 *
 * @package WP\Console\Generator
 */
class QuickTagGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var TranslatorManager
     */
    protected $translatorManager;

    /**
     * QuickTagGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * Generate.
     *
     * @param string $extension_type
     * @param string $extension
     * @param string $function_name
     * @param string $quicktag_items
     * @param string $site
     */
    public function generate(
        $extension_type,
        $extension,
        $function_name,
        $quicktag_items,
        $site
    ) {
        if ($extension_type == "plugin") {
            $extensionFile = $this->extensionManager->getPlugin($extension);
            $dir = 'admin/js/QuickTag/quicktags.js';
            $renderArray =['/plugin.php', 'src/QuickTag/quicktag.js', ''];
        } else {
            $extensionFile = $this->extensionManager->getTheme($extension);
            $dir = 'src/QuickTag/quicktags.php';
            $renderArray = ['/functions.php', 'src/QuickTag/quicktag.php', '/functions.php' ];
        }

        $parameters = [
            $extension_type => $extension,
            'function_name' => $function_name,
            'quicktag_items' => $quicktag_items,
            'class_name_quicktag_path' => $dir,
            'file_exists' => file_exists($extensionFile->getPathname()),
            'file_exists_quicktag' => file_exists($extensionFile->getPath().'/'.$dir)
        ];

        $site->loadLegacyFile($extensionFile->getPath().'/'.$dir);

        if (function_exists($function_name) && $extension_type == "theme") {
            throw new \RuntimeException(
                sprintf(
                    'Unable to generate the quicktag , The function name already exist at "%s"',
                    realpath($extensionFile->getPath().'/'.$dir)
                )
            );
        }


        if (!file_exists($extensionFile->getPath().'/'.$dir)) {
            $this->renderFile(
                $extension_type.$renderArray[0].'.twig',
                $extensionFile->getPathname().$renderArray[2],
                $parameters,
                FILE_APPEND
            );
        }

        $this->renderFile(
            $extension_type.'/'.$renderArray[1].'.twig',
            $extensionFile->getPath().'/'.$dir,
            $parameters,
            FILE_APPEND
        );
    }
}
