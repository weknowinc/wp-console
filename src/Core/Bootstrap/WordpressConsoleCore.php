<?php

namespace WP\Console\Core\Bootstrap;

use Symfony\Component\Config\FileLocator;
use WP\Console\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

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
     * Wordoress Console constructor.
     * @param $root
     * @param $appRoot
     */
    public function __construct($root, $appRoot = null)
    {
        $this->appRoot = $appRoot;
        $this->root  = $root;
    }

    /**
     * @return null|ContainerBuilder
     */
    public function boot()
    {

        // Validate that Wordpress load files is available
        if (!empty($this->appRoot) && is_dir($this->appRoot) && file_exists($this->appRoot . '/wp-load.php')) {
            // Load the WordPress library.
            require_once($this->appRoot . '/wp-load.php');
        } else {
            return null;
        }

        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator($this->root));
        $loader->load($this->root .'/services.yml');

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
