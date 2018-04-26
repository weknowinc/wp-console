<?php

/**
 * @file
 * Contains WP\Console\Core\Generator\InitGenerator.
 */
namespace WP\Console\Core\Generator;

use WP\Console\Core\Utils\NestedArray;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

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
     * InitGenerator constructor.
     *
     * @param NestedArray $nestedArray
     */
    public function __construct(
        NestedArray $nestedArray
    ) {
        $this->nestedArray = $nestedArray;
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
        $configParameters = array_map(
            function ($item) {
                if (is_bool($item)) {
                    return $item?"true":"false";
                }
                return $item;
            },
            $configParameters
        );

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
            $this->resetStatisticsConfig($userHome, $configParameters['statistics']);
            unset($configParameters['statistics']);
        }

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

    /**
     * Reset only the value of statistics is the init command is an override.
     *
     * @param  $homeDirectory
     * @param  $statisticsValue
     * @return int
     */
    private function resetStatisticsConfig($homeDirectory, $statisticsValue)
    {
        $parser = new Parser();
        $dumper = new Dumper();

        $userConfigFile = $homeDirectory . 'config.yml';

        if (!file_exists($userConfigFile)) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.settings.set.messages.missing-file'),
                    $userConfigFile
                )
            );
            return 1;
        }

        try {
            $userConfigFileParsed = $parser->parse(
                file_get_contents($userConfigFile)
            );
        } catch (\Exception $e) {
        }

        $parents = array_merge(['application'], ['share', 'statistics']);

        $this->nestedArray->setValue(
            $userConfigFileParsed,
            $parents,
            filter_var($statisticsValue, FILTER_VALIDATE_BOOLEAN),
            true
        );

        try {
            $userConfigFileDump = $dumper->dump($userConfigFileParsed, 10);
        } catch (\Exception $e) {
        }

        try {
            file_put_contents($userConfigFile, $userConfigFileDump);
        } catch (\Exception $e) {
        }
    }
}
