<?php

namespace WP\Console\Core\Bootstrap;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use WP\Console\Core\DependencyInjection\ContainerBuilder;
use WP\Console\Utils\Site;
use WP\Console\Core\Utils\ArgvInputReader;

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

        // Validate that Wordpress load files is available
        if ($config = $this->site->getConfig()) {
            // Include files to define basic wordpress constants and variables
            try {
                $uri = $argvInputReader->get('uri');
                $this->site->setGlobalServer($uri);

                if($uri == 'http://default') {
                    $constants = $this->site->extractConstants($config);
                    if (isset($constants['MULTISITE']) && $constants['MULTISITE'] = 'true') {
                        if (isset($constants['MULTISITE'])) {
                            $this->site->setGlobalServer($constants['DOMAIN_CURRENT_SITE'], $constants['PATH_CURRENT_SITE']);
                        }
                    }
                }
                $this->site->loadLegacyFile('wp-load.php');
            } catch(\Exception $e) {
                echo $e->getMessage();
            }

            $loader->load($this->root . '/services.yml');

            if($this->site->isMultisite()) {
                $loader->load($this->root . '/services-multisite.yml');

            } else {
                $loader->load($this->root . '/services-multisite-install.yml');
            }
        } else {
            $loader->load($this->root . '/services-install.yml');
            $loader->load($this->root . '/services-multisite-install.yml');
        }


        $container->get('console.configuration_manager')
            ->loadConfiguration($this->root)
            ->getConfiguration();

        $container->set(
            'app.root',
            $this->appRoot
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

        return $container;
    }
}
