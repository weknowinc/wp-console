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
     * @param array  $quicktag_items
     * @param Site   $site
     */
    public function generate(
        $extension_type,
        $extension,
        $function_name,
        $quicktag_items,
        $site
    ) {
        $extensionObject = $this->extensionManager->getWPExtension($extension_type, $extension);


        $parameters = [
            $extension_type => $extension,
            "function_name" => $function_name,
            "quicktag_items" => $quicktag_items,
            "class_name_quicktag_path" => $extension_type == "theme" ? 'src/QuickTag/quicktags.php':'admin/js/QuickTag/quicktags.js',
            "file_exists" => file_exists($extensionObject->getPathName().($extension_type == "theme" ? '/functions.php':'')),
        ];

        $file_path_admin = $extensionObject->getPath().'/'.$parameters['class_name_quicktag_path'];
        $parameters['admin_file_exists'] = file_exists($file_path_admin);

        $site->loadLegacyFile($file_path_admin);

        if (function_exists($function_name) && $extension_type == "theme") {
            throw new \RuntimeException(
                sprintf(
                    'Unable to generate the quicktag , The function name already exist at "%s"',
                    realpath($file_path_admin)
                )
            );
        }


        if (!file_exists($file_path_admin)) {
            $this->renderFile(
                $extension_type.($extension_type == "theme" ? '/functions.php':'/plugin.php').'.twig',
                $extensionObject->getPathname() . ($extension_type == "theme" ? '/functions.php':''),
                $parameters,
                FILE_APPEND
            );
        }

        $this->renderFile(
            $extension_type . ($extension_type == "theme" ? '/src/QuickTag/quicktag.php':'/src/QuickTag/quicktag.js').'.twig',
            $file_path_admin,
            $parameters,
            FILE_APPEND
        );
    }
}
