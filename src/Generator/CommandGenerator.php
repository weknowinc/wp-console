<?php

/**
 * @file
 * Contains \WP\Console\Generator\CommandGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Extension\Manager;
use WP\Console\Core\Utils\TranslatorManager;
use WP\Console\Core\Generator\Generator;

/**
 * Class CommandGenerator
 *
 * @package WP\Console\Generator
 */
class CommandGenerator extends Generator
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
     * CommandGenerator constructor.
     *
     * @param Manager                    $extensionManager
     * @param TranslatorManager $translatorManager
     */
    public function __construct(
        Manager $extensionManager,
        TranslatorManager $translatorManager
    ) {
        $this->extensionManager = $extensionManager;
        $this->translatorManager = $translatorManager;
    }

    /**
     * Generate.
     *
     * @param string  $extension      Extension name
     * @param string  $name           Command name
     * @param string  $class          Class name
     * @param array   $services       Services array
     */
    public function generate($plugin, $pluginNameSpace, $pluginCamelCaseMachineName, $name, $class, $services)
    {
        $command_key = str_replace(':', '.', $name);
        
        $parameters = [
            'plugin' => $plugin,
            'pluginNameSpace' => $pluginNameSpace,
            'name' => $name,
            'class_name' => $class,
            'command_key' => $command_key,
            'services' => $services,
            'tags' => ['name' => 'wordpress.command'],
            'class_path' => sprintf('WP\%s\Command\%s', $pluginNameSpace, $class),
            'file_exists' => file_exists($this->extensionManager->getPlugin($plugin)->getPath() .'/console.services.yml'),
        ];

        $this->renderFile(
            'plugin/src/Command/command.php.twig',
            $this->extensionManager->getPlugin($plugin)->getCommandDirectory().$class.'.php',
            $parameters
        );

        $parameters['name'] = $pluginCamelCaseMachineName.'.'.str_replace(':', '_', $name);

        $this->renderFile(
            'plugin/services.yml.twig',
            $this->extensionManager->getPlugin($plugin)->getPath() .'/console.services.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'plugin/src/Command/console/translations/en/command.yml.twig',
            $this->extensionManager->getPlugin($plugin)->getPath() .'/console/translations/en/'.$command_key.'.yml'
        );
    }
}
