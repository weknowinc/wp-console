<?php

namespace WP\Console\Bootstrap;

use WP\Console\Core\Bootstrap\WordpressConsoleCore;
use WP\Console\Bootstrap\WordpressServiceModifier;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Core\Utils\TranslatorManager;
use WP\Console\Utils\Site;


class Wordpress
{
    protected $autoload;
    protected $root;
    protected $appRoot;

    /**
     * @var Site
     */
    protected $site;

    /**
     * Wordpress constructor.
     * @param $autoload
     * @param $appRoot
     */
    public function __construct($autoload, $root, $appRoot)
    {

        $this->autoload = $autoload;
        $this->root = $root;
        $this->appRoot = $appRoot;
        $this->site = new Site($appRoot);
    }

    public function boot()
    {
        $wordpress = new WordpressConsoleCore($this->root, $this->appRoot, $this->site);
        $container = $wordpress->boot();

        $this->addServiceModifier(
            new WordpressServiceModifier(
                $this->root,
                'wordpress.command',
                'wordpress.generator'
            )
        );

        foreach ($this->serviceModifiers as $serviceModifier) {
            $serviceModifier->alter($container);
        }

        return $wordpress->boot();
    }

    /**
     * @param \WP\Console\Bootstrap\WordpressServiceModifier $serviceModifier
     */
    public function addServiceModifier(WordpressServiceModifier $serviceModifier)
    {
        $this->serviceModifiers[] = $serviceModifier;
    }
}
