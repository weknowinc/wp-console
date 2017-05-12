<?php

namespace WP\Console\Bootstrap;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use WP\Console\Utils\TranslatorManager;
use WP\Console\Extension\Extension;
use WP\Console\Extension\Manager;

/**
 * FindCommandsCompilerPass
 */
class AddServicesCompilerPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    protected $root;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var boolean
     */
    protected $rebuild;

    /**
     * AddCommandsCompilerPass constructor.
     *
     * @param string  $root
     * @param string  $appRoot
     * @param boolean $rebuild
     */
    public function __construct($root, $appRoot, $rebuild = false)
    {
        $this->root = $root;
        $this->appRoot = $appRoot;
        $this->rebuild = $rebuild;
    }

    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $servicesData = [];

        $loader = new YamlFileLoader(
            $container,
            new FileLocator($this->root)
        );

        /**
         * @var Manager $extensionManager
         */
        $extensionManager = $container->get('console.extension_manager');

        /**
         * @var Extension[] $modules
         */
        $plugins = $extensionManager->discoverPlugins()
            ->showCore()
            ->showNoCore()
            ->showActivated()
            ->getList(false);

        foreach ($plugins as $plugin) {
            $consoleServicesExtensionFile = $this->appRoot . '/' .
                $extensionManager->getPlugin($plugin['Name'])->getPath()  . '/console.services.yml';

            print $consoleServicesExtensionFile . PHP_EOL;

            if (is_file($consoleServicesExtensionFile)) {
                $loader->load($consoleServicesExtensionFile);
            }
        }
    }
}
