<?php

/**
 * @file
 * Contains WP\Console\Core\Generator\InitGenerator.
 */
namespace WP\Console\Core\Generator;

use WP\Console\Core\Utils\ConfigurationManager;
use WP\Console\Core\Utils\NestedArray;

/**
 * Class InitGenerator
 *
 * @package WP\Console\Core\Generator
 */
class InitGenerator extends Generator
{
    /**
     * @var NestedArray
     */
    protected $nestedArray;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * InitGenerator constructor.
     *
     * @param NestedArray          $nestedArray
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(NestedArray $nestedArray, ConfigurationManager $configurationManager)
    {
        $this->nestedArray = $nestedArray;
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param string  $userHome
     * @param string  $executableName
     * @param boolean $override
     * @param string  $destination
     * @param array   $configParameters
     */
    public function generate(
        $userHome,
        $executableName,
        $override,
        $destination,
        $configParameters
    ) {
        $configFile = $userHome . 'config.yml';
        if ($destination) {
            $configFile = $destination.'config.yml';
        }

        if (file_exists($configFile) && $override) {
            copy(
                $configFile,
                $configFile . '.old'
            );
        }

        // If configFile is an override, we only change the value of statistics in the global config.
        $consoleDestination = $userHome . 'config.yml';
        if ($configFile !== $consoleDestination) {
            $this->configurationManager->updateConfigGlobalParameter(
                'statistics.enabled',
                $configParameters['statistics']
            );

            unset($configParameters['statistics']);
        }

        $configParameters = array_map(
            function ($item) {
                if (is_bool($item)) {
                    return $item?"true":"false";
                }
                return $item;
            },
            $configParameters
        );

        $this->renderFile(
            'core/init/config.yml.twig',
            $configFile,
            $configParameters
        );

        if ($executableName) {
            $parameters = [
                'executable' => $executableName,
            ];

            $this->renderFile(
                'core/autocomplete/console.rc.twig',
                $userHome . 'console.rc',
                $parameters
            );

            $this->renderFile(
                'core/autocomplete/console.fish.twig',
                $userHome . 'drupal.fish',
                $parameters
            );
        }
    }
}
