<?php

namespace WP\Console\Core\Bootstrap;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use WP\Console\Core\DependencyInjection\ContainerBuilder;
use WP\Console\Utils\Site;

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
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator($this->root));
        $loader->load($this->root . '/services-core.yml');

        // Validate that Wordpress load files is available
        if ($config = $this->site->getConfig()) {
            // Include files to define basic wordpress constants and variables,
            $this->site->loadLegacyFile('wp-load.php');

            $loader->load($this->root . '/services.yml');

        } else {
            // Include files to define basic wordpress constants and variables,
            #define( 'ABSPATH', $this->appRoot . '/');
//            define( 'ABSPATH', $this->appRoot . '/' );
//            define( 'WPINC', 'wp-includes' );
//
//            $this->site->loadLegacyFile('wp-includes/load.php');
//            $this->site->loadLegacyFile('wp-includes/functions.php');
//            $this->site->loadLegacyFile('wp-includes/plugin.php');
//            $this->site->loadLegacyFile('wp-includes/cache.php');
//
//            // Standardize $_SERVER variables across setups.
//            wp_fix_server_vars();

            $loader->load($this->root . '/services-wordpress-install.yml');
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
