<?php

/**
 * @file
 * Contains \WP\Console\Generator\CommandGenerator.
 */

namespace WP\Console\Generator;

use WP\Console\Extension\Manager;
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
     * CommandGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(Manager $extensionManager)
    {
        $this->extensionManager = $extensionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $plugin = $parameters['plugin'];
        $class = $parameters['class_name'];
        $name = $parameters['name'];

        //$pluginCamelCaseMachineName
        $command_key = str_replace(':', '.', $name);


        $parameters = array_merge(
            $parameters, [
            'command_key' => $command_key,
            'tags' => ['name' => 'wordpress.command'],
            'class_path' => sprintf('WP\%s\Command\%s', $parameters['pluginNameSpace'], $class),
            'file_exists' => file_exists($this->extensionManager->getPlugin($plugin)->getPath() .'/console.services.yml'),
            ]
        );


        $this->renderFile(
            'plugin/src/Command/command.php.twig',
            $this->extensionManager->getPlugin($plugin)->getCommandDirectory().$class.'.php',
            $parameters
        );

        $parameters['name'] = $parameters['pluginCamelCaseMachineName'].'.'.str_replace(':', '_', $name);
        unset($parameters['pluginCamelCaseMachineName']);

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
