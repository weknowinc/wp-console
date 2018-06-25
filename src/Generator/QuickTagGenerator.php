<?php

/**
 * @file
 * Contains \WP\Console\Generator\QuickTagGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Extension\Manager;
use WP\Console\Core\Generator\Generator;
use WP\Console\Utils\Site;

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
     * {@inheritdoc}
     */
    public function generate(array $parameters, Site $site)
    {
        $extensionType = $parameters['extension_type'];
        $extension = $parameters['extension'];

        unset($parameters['extension_type']);
        unset($parameters['extension']);

        $extensionObject = $this->extensionManager->getWPExtension($extensionType, $extension);


        $parameters = array_merge(
            $parameters, [
            $extensionType => $extension,
            "class_name_quicktag_path" => $extensionType == "theme" ? 'src/QuickTag/quicktags.php':'admin/js/QuickTag/quicktags.js',
            "file_exists" => file_exists($extensionObject->getPathName().($extensionType == "theme" ? '/functions.php':'')),
            ]
        );

        $file_path_admin = $extensionObject->getPath().'/'.$parameters['class_name_quicktag_path'];
        $parameters['admin_file_exists'] = file_exists($file_path_admin);

        $site->loadLegacyFile($file_path_admin);

        if (function_exists($parameters['function_name']) && $extensionType == "theme") {
            throw new \RuntimeException(
                sprintf(
                    'Unable to generate the quicktag , The function name already exist at "%s"',
                    realpath($file_path_admin)
                )
            );
        }


        if (!file_exists($file_path_admin)) {
            $this->renderFile(
                $extensionType.($extensionType == "theme" ? '/functions.php':'/plugin.php').'.twig',
                $extensionObject->getPathname() . ($extensionType == "theme" ? '/functions.php':''),
                $parameters,
                FILE_APPEND
            );
        }

        $this->renderFile(
            $extensionType . ($extensionType == "theme" ? '/src/QuickTag/quicktag.php':'/src/QuickTag/quicktag.js').'.twig',
            $file_path_admin,
            $parameters,
            FILE_APPEND
        );
    }
}
