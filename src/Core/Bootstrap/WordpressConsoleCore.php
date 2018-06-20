<?php

namespace WP\Console\Core\Bootstrap;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use WP\Console\Core\DependencyInjection\ContainerBuilder;
use WP\Console\Utils\Site;
use WP\Console\Core\Utils\ArgvInputReader;
use WP\Console\Core\Site\Settings;
use WP\Console\Component\FileCache\FileCacheFactory;
use WP\Console\Utils\TranslatorManager;

class WordpressConsoleCore
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
     * @var Site
     */
    protected $site;

    /**
     * Wordoress Console constructor.
     *
     * @param $root
     * @param $appRoot
     */
    public function __construct($root, $appRoot = null, Site $site)
    {
        $this->appRoot = $appRoot;
        $this->root  = $root;
        $this->site = $site;
    }

    /**
     * @return null|ContainerBuilder
     */
    public function boot()
    {
        $argvInputReader = new ArgvInputReader();
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator($this->root));
        $loader->load($this->root . '/services-core.yml');

        $configurationManager = $container->get('console.configuration_manager');

        // Validate that Wordpress load files is available
        if ($config = $this->site->getConfig()) {
            // Include files to define basic wordpress constants and variables
            try {
                $uri = $argvInputReader->get('uri');
                $this->site->setGlobalServer($uri);

                if ($uri == 'http://default') {
                    $constants = $this->site->extractConstants($config);
                    if (isset($constants['MULTISITE']) && $constants['MULTISITE'] = 'true') {
                        if (isset($constants['MULTISITE'])) {
                            $this->site->setGlobalServer($constants['DOMAIN_CURRENT_SITE'], $constants['PATH_CURRENT_SITE']);
                        }
                    }
                } else {
                    $host =  parse_url($uri, PHP_URL_HOST);
                    $path =  parse_url($uri, PHP_URL_PATH);
                    $this->site->setGlobalServer($host, $path);
                }

                $this->site->loadLegacyFile('wp-load.php');
            } catch (\Exception $e) {
                echo $e->getMessage();
            }

            $loader->load($this->root . '/services.yml');

            // Register services commands
            $finder = new Finder();

            $finder->files()
                ->name('*.yml')
                ->in(
                    sprintf(
                        '%s/config/services/wp-console',
                        $this->root
                    )
                );

            foreach ($finder as $file) {
                $loader->load($file->getPathName());
            }

            if ($this->site->isMultisite()) {
                $loader->load($this->root . '/services-multisite.yml');
            } else {
                $loader->load($this->root . '/services-multisite-install.yml');
            }
        } else {
            $loader->load($this->root . '/services-install.yml');
            $loader->load($this->root . '/services-multisite-install.yml');
        }

        $configurationManager
            ->loadConfiguration($this->root . '/')
            ->getConfiguration();

        // Register extend commands
        $directory = $configurationManager->getConsoleConfigGlobalDirectory() . 'extend/';
        $autoloadFile = $directory . 'vendor/autoload.php';
        if (is_file($autoloadFile)) {
            include_once $autoloadFile;
            $extendService = $directory . 'extend.console.services.yml';
            if (is_file($extendService)) {
                $loader->load($extendService);
            }
        }

        $appRoot = $this->appRoot?$this->appRoot:$this->root;
        // Set service app.root
        $container->set(
            'app.root',
            $appRoot
        );

        $container->get('console.translator_manager')
            ->loadCoreLanguage('en', $this->root . '/config/translations/');

        if (stripos($this->root, '/bin/') <= 0) {
            $container->set(
                'console.root',
                $this->root
            );
        }

        $container->get('console.renderer')
            ->setSkeletonDirs(
                [
                    $this->root.'/templates/',
                ]
            );

        // Initialize the FileCacheFactory component. We have to do it here instead
        // of in \WP\Console\Component\FileCache\FileCacheFactory because we can not use
        // the Settings object in a component.
        $configuration = Settings::get('file_cache');

        // Provide a default configuration, if not set.
        if (!isset($configuration['default'])) {
            if (function_exists('apcu_fetch')) {
                $configuration['default']['cache_backend_class'] = '\WP\Console\Component\FileCache\ApcuFileCacheBackend';
            }
        }
        FileCacheFactory::setConfiguration($configuration);
        FileCacheFactory::setPrefix(Settings::getApcuPrefix('file_cache', $this->root));

        /* Load plugin custom commands */
        /* @TODO Implemt WP\Console\Bootstrap\AddServicesCompilerPass

        /**
         * @var Manager $extensionManager
         */
        $extensionManager = $container->get('console.extension_manager');

        if ($this->site->getConfig()) {
            $finder = new Finder();

            /**
             * @var Extension[] $modules
             */
            $plugins = $extensionManager->discoverPlugins()
                ->showCore()
                ->showNoCore()
                ->showActivated()
                ->getList(false);

            foreach ($plugins as $plugin) {
                $pluginPath = $this->appRoot . '/' . $extensionManager->getPlugin($plugin['Name'])->getPath();

                if (file_exists($pluginPath . '/src/Command')) {
                    $finder->files()->in($pluginPath . '/src/Command');
                    foreach ($finder as $command) {
                        include_once $command->getRealPath();
                    }

                    $consoleServicesExtensionFile = $pluginPath . '/console.services.yml';

                    if (is_file($consoleServicesExtensionFile)) {
                        $loader->load($consoleServicesExtensionFile);
                    }
                }
            }
        }

        $container->setParameter(
            'console.service_definitions',
            $container->getDefinitions()
        );

        return $container;
    }
}
