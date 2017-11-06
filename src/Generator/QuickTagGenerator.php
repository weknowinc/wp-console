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
     * @param array $quicktag_items
     * @param Site $site
     */
    public function generate(
        $extension_type,
        $extension,
        $function_name,
        $quicktag_items,
        $site
    ) {

        $extensionObject = $this->extensionManager->getWPExtension($extension_type, $extension);

        $extensionFiles = [
            "plugin" =>
                [
                    "dir"=> 'admin/js/QuickTag/quicktags.js',
                    "render" => ['/plugin.php', 'src/QuickTag/quicktag.js', '']
                ],
            "theme" =>
                [
                    "dir"=> 'src/QuickTag/quicktags.php',
                    "render" => ['/functions.php', 'src/QuickTag/quicktag.php', '/functions.php' ]
                ]
        ];

        $parameters = [
            $extension_type => $extension,
            "function_name" => $function_name,
            "quicktag_items" => $quicktag_items,
            "class_name_quicktag_path" => $extensionFiles[$extension_type]['dir'],
            "file_exists" => file_exists($extensionObject->getPathName()),
            "function_exists" => file_exists($extensionObject->getPath().$extensionFiles['theme']['render'])
        ];

        $site->loadLegacyFile($extensionObject->getPath().'/'.$extensionFiles[$extension_type]['dir']);

        if (function_exists($function_name) && $extension_type == "theme") {
            throw new \RuntimeException(
                sprintf(
                    'Unable to generate the quicktag , The function name already exist at "%s"',
                    realpath($extensionObject->getPath().'/'.$extensionFiles[$extension_type]['dir'])
                )
            );
        }


        if (!file_exists($extensionObject->getPath().'/'.$extensionFiles[$extension_type]['dir'])) {
            $this->renderFile(
                $extension_type.$extensionFiles[$extension_type]['render'][0].'.twig',
                $extensionObject->getPathname().$extensionFiles[$extension_type]['render'][2],
                $parameters,
                FILE_APPEND
            );
        }

        $this->renderFile(
            $extension_type.'/'.$extensionFiles[$extension_type]['render'][1].'.twig',
            $extensionObject->getPath().'/'.$extensionFiles[$extension_type]['dir'],
            $parameters,
            FILE_APPEND
        );
    }
}
